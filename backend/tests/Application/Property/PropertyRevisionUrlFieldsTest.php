<?php

declare(strict_types=1);

namespace App\Tests\Application\Property;

use App\Application\Command\Property\ApproveRevision\ApproveRevisionCommand;
use App\Application\Command\Property\ApproveRevision\ApproveRevisionHandler;
use App\Application\Command\Property\UpdateProperty\UpdatePropertyCommand;
use App\Application\Command\Property\UpdateProperty\UpdatePropertyHandler;
use App\Domain\Exchange\Repository\ExchangeRateRepositoryInterface;
use App\Domain\Property\Entity\Property;
use App\Domain\Property\Entity\PropertyRevision;
use App\Domain\Property\Repository\MetroStationRepositoryInterface;
use App\Domain\Property\Repository\PropertyRepositoryInterface;
use App\Domain\Property\Repository\PropertyMetroStationRepositoryInterface;
use App\Domain\Property\Repository\PropertyRevisionRepositoryInterface;
use App\Domain\Property\ValueObject\Address;
use App\Domain\Property\ValueObject\Coordinates;
use App\Domain\Property\ValueObject\Price;
use App\Domain\Shared\ValueObject\Id;
use App\Infrastructure\Service\ExchangeRateService;
use App\Infrastructure\Service\MetroProximityCalculator;
use Doctrine\ORM\EntityManagerInterface;
use PHPUnit\Framework\TestCase;
use Psr\Log\LoggerInterface;
use Symfony\Component\Messenger\Envelope;
use Symfony\Component\Messenger\MessageBusInterface;
use Symfony\Contracts\HttpClient\HttpClientInterface;

final class PropertyRevisionUrlFieldsTest extends TestCase
{
    public function testPublishedUpdateStoresUrlFieldsInRevisionData(): void
    {
        $property = $this->createProperty(ownerId: 1, propertyId: 300);
        $property->setStatus('published');
        $property->setInstagramUrl('https://instagram.com/old');
        $property->setWebsiteUrl('https://example.com/old');
        $property->setVideoUrl('https://www.youtube.com/watch?v=old123');

        $savedRevision = null;
        $revisionRepository = $this->createMock(PropertyRevisionRepositoryInterface::class);
        $revisionRepository->method('findLatestByPropertyAndStatus')->willReturn(null);
        $revisionRepository
            ->expects(self::once())
            ->method('save')
            ->willReturnCallback(static function (PropertyRevision $revision) use (&$savedRevision): void {
                $savedRevision = $revision;
            });

        $propertyRepository = $this->createMock(PropertyRepositoryInterface::class);
        $propertyRepository->method('findById')->willReturn($property);
        $propertyRepository->expects(self::never())->method('save');

        $handler = new UpdatePropertyHandler(
            $propertyRepository,
            $revisionRepository,
            $this->createExchangeRateService(['USD' => 3.2]),
            $this->createMetroCalculator(),
        );

        $requiresModeration = $handler(new UpdatePropertyCommand(
            propertyId: '300',
            userId: '1',
            instagramUrl: 'https://instagram.com/new',
            websiteUrl: 'https://example.com/new',
            videoUrl: 'https://www.youtube.com/watch?v=new456',
        ));

        self::assertTrue($requiresModeration);
        self::assertNotNull($savedRevision);
        self::assertSame('https://instagram.com/new', $savedRevision->getData()['instagramUrl'] ?? null);
        self::assertSame('https://example.com/new', $savedRevision->getData()['websiteUrl'] ?? null);
        self::assertSame('https://www.youtube.com/watch?v=new456', $savedRevision->getData()['videoUrl'] ?? null);
        self::assertSame('https://instagram.com/old', $property->getInstagramUrl());
        self::assertSame('https://example.com/old', $property->getWebsiteUrl());
        self::assertSame('https://www.youtube.com/watch?v=old123', $property->getVideoUrl());
    }

    public function testApproveRevisionAppliesUrlFieldsAndAllowsClearing(): void
    {
        $property = $this->createProperty(ownerId: 1, propertyId: 301);
        $property->setStatus('published');
        $property->setInstagramUrl('https://instagram.com/old');
        $property->setWebsiteUrl('https://example.com/old');
        $property->setVideoUrl('https://www.youtube.com/watch?v=old123');

        $revision = new PropertyRevision($property, [
            'instagramUrl' => '',
            'websiteUrl' => 'https://example.com/new',
            'videoUrl' => 'https://www.tiktok.com/@user/video/7123456789012345678',
        ]);
        $revisionIdReflection = new \ReflectionProperty($revision, 'id');
        $revisionIdReflection->setAccessible(true);
        $revisionIdReflection->setValue($revision, Id::fromInt(9001));

        $propertyRepository = $this->createMock(PropertyRepositoryInterface::class);
        $propertyRepository->method('findById')->willReturn($property);
        $propertyRepository->expects(self::exactly(2))->method('save')->with($property);

        $revisionRepository = $this->createMock(PropertyRevisionRepositoryInterface::class);
        $revisionRepository->method('findById')->willReturn($revision);
        $revisionRepository->expects(self::once())->method('save')->with($revision);

        $handler = new ApproveRevisionHandler(
            $propertyRepository,
            $revisionRepository,
            $this->createExchangeRateService(['USD' => 3.2]),
            $this->createMetroCalculator(),
            new class implements MessageBusInterface {
                public function dispatch(object $message, array $stamps = []): Envelope
                {
                    return new Envelope($message);
                }
            },
        );

        $handler(new ApproveRevisionCommand(
            propertyId: '301',
            revisionId: '9001',
        ));

        self::assertNull($property->getInstagramUrl());
        self::assertSame('https://example.com/new', $property->getWebsiteUrl());
        self::assertSame('https://www.tiktok.com/@user/video/7123456789012345678', $property->getVideoUrl());
    }

    public function testPriceOnlyUpdateDoesNotApplyWhenVideoUrlChanges(): void
    {
        $property = $this->createProperty(ownerId: 1, propertyId: 302);
        $property->setStatus('published');
        $property->setVideoUrl('https://www.youtube.com/watch?v=old123');
        $property->setImages([
            '/uploads/properties/a.jpg',
            '/uploads/properties/b.jpg',
            '/uploads/properties/c.jpg',
        ]);

        $revisionRepository = $this->createMock(PropertyRevisionRepositoryInterface::class);
        $revisionRepository->method('findLatestByPropertyAndStatus')->willReturn(null);
        $revisionRepository->expects(self::once())->method('save');

        $propertyRepository = $this->createMock(PropertyRepositoryInterface::class);
        $propertyRepository->method('findById')->willReturn($property);
        $propertyRepository->expects(self::never())->method('save');

        $handler = new UpdatePropertyHandler(
            $propertyRepository,
            $revisionRepository,
            $this->createExchangeRateService(['USD' => 3.2]),
            $this->createMetroCalculator(),
        );

        $requiresModeration = $handler(new UpdatePropertyCommand(
            propertyId: '302',
            userId: '1',
            title: $property->getTitle(),
            description: $property->getDescription(),
            type: $property->getType(),
            dealType: $property->getDealType(),
            priceAmount: 10_000_000,
            priceCurrency: 'BYN',
            area: $property->getArea(),
            rooms: $property->getRooms(),
            floor: $property->getFloor(),
            totalFloors: $property->getTotalFloors(),
            bathrooms: $property->getBathrooms(),
            yearBuilt: $property->getYearBuilt(),
            building: $property->getAddress()->getBuilding(),
            cityId: $property->getCityId(),
            latitude: $property->getCoordinates()->getLatitude(),
            longitude: $property->getCoordinates()->getLongitude(),
            images: $property->getImages(),
            amenities: $property->getAmenities(),
            maxDailyGuests: $property->getMaxDailyGuests(),
            dailySingleBeds: $property->getDailySingleBeds(),
            dailyDoubleBeds: $property->getDailyDoubleBeds(),
            videoUrl: 'https://www.youtube.com/watch?v=new456',
        ));

        self::assertTrue($requiresModeration);
        self::assertSame('https://www.youtube.com/watch?v=old123', $property->getVideoUrl());
    }

    private function createProperty(int $ownerId, int $propertyId): Property
    {
        $property = new Property(
            ownerId: Id::fromInt($ownerId),
            type: 'house',
            dealType: 'daily',
            title: 'Original title',
            description: 'Original description long enough for updates.',
            price: Price::fromAmount(9000000, 'BYN'),
            area: 70.0,
            rooms: 3,
            floor: 4,
            totalFloors: 10,
            bathrooms: 1,
            yearBuilt: 2012,
            renovation: null,
            balcony: null,
            livingArea: null,
            kitchenArea: null,
            dealConditions: null,
            paymentMethods: null,
            maxDailyGuests: 4,
            dailySingleBeds: 1,
            dailyDoubleBeds: 1,
            checkInTime: null,
            checkOutTime: null,
            address: Address::create('15', null),
            cityId: 1,
            coordinates: Coordinates::create(53.9045, 27.5615),
            landArea: 10.0,
        );

        $reflection = new \ReflectionProperty($property, 'id');
        $reflection->setAccessible(true);
        $reflection->setValue($property, Id::fromInt($propertyId));

        return $property;
    }

    private function createExchangeRateService(array $rates): ExchangeRateService
    {
        $rateRepository = $this->createStub(ExchangeRateRepositoryInterface::class);
        $rateRepository->method('getAllRates')->willReturn($rates);

        return new ExchangeRateService(
            $rateRepository,
            $this->createStub(EntityManagerInterface::class),
            $this->createStub(HttpClientInterface::class),
            $this->createStub(LoggerInterface::class)
        );
    }

    private function createMetroCalculator(): MetroProximityCalculator
    {
        $propertyMetroStationRepository = $this->createStub(PropertyMetroStationRepositoryInterface::class);
        $metroStationRepository = $this->createStub(MetroStationRepositoryInterface::class);
        $metroStationRepository->method('findAll')->willReturn([]);

        return new MetroProximityCalculator($metroStationRepository, $propertyMetroStationRepository);
    }
}
