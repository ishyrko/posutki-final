<?php

declare(strict_types=1);

namespace App\Tests\Application\Property;

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
use Symfony\Contracts\HttpClient\HttpClientInterface;

final class UpdatePropertyHandlerTest extends TestCase
{
    public function testPublishedPropertyUpdateCreatesRevisionAndDoesNotMutateOriginal(): void
    {
        $propertyRepository = $this->createMock(PropertyRepositoryInterface::class);
        $revisionRepository = $this->createMock(PropertyRevisionRepositoryInterface::class);
        $propertyMetroStationRepository = $this->createMock(PropertyMetroStationRepositoryInterface::class);
        $metroCalculator = $this->createMetroCalculator($propertyMetroStationRepository);
        $exchangeRateService = $this->createExchangeRateService(['USD' => 3.2]);

        $property = $this->createProperty(ownerId: 1, propertyId: 200);
        $property->setStatus('published');
        $originalTitle = $property->getTitle();

        $propertyRepository
            ->method('findById')
            ->willReturn($property);

        $revisionRepository
            ->method('findLatestByPropertyAndStatus')
            ->willReturn(null);

        $revisionRepository
            ->expects(self::once())
            ->method('save')
            ->with(self::callback(static function (PropertyRevision $revision) use ($property): bool {
                return $revision->getProperty() === $property
                    && ($revision->getData()['title'] ?? null) === 'Updated title';
            }));

        $propertyRepository->expects(self::never())->method('save');
        $propertyMetroStationRepository->expects(self::never())->method('deleteByPropertyId');

        $handler = new UpdatePropertyHandler(
            $propertyRepository,
            $revisionRepository,
            $exchangeRateService,
            $metroCalculator,
        );

        $handler(new UpdatePropertyCommand(
            propertyId: '200',
            userId: '1',
            title: 'Updated title',
            description: 'Updated description that should go to revision data'
        ));

        self::assertSame($originalTitle, $property->getTitle());
    }

    public function testDraftPropertyUpdateMutatesEntityAndSavesDirectly(): void
    {
        $propertyRepository = $this->createMock(PropertyRepositoryInterface::class);
        $revisionRepository = $this->createMock(PropertyRevisionRepositoryInterface::class);
        $propertyMetroStationRepository = $this->createMock(PropertyMetroStationRepositoryInterface::class);
        $metroCalculator = $this->createMetroCalculator($propertyMetroStationRepository);
        $exchangeRateService = $this->createExchangeRateService(['USD' => 3.0]);

        $property = $this->createProperty(ownerId: 1, propertyId: 201);

        $propertyRepository
            ->method('findById')
            ->willReturn($property);

        $propertyRepository
            ->expects(self::exactly(2))
            ->method('save')
            ->with($property);

        $revisionRepository->expects(self::never())->method('save');
        $propertyMetroStationRepository->expects(self::once())->method('deleteByPropertyId')->with(201);

        $handler = new UpdatePropertyHandler(
            $propertyRepository,
            $revisionRepository,
            $exchangeRateService,
            $metroCalculator,
        );

        $handler(new UpdatePropertyCommand(
            propertyId: '201',
            userId: '1',
            title: 'Draft updated title',
            priceAmount: 100000,
            priceCurrency: 'USD'
        ));

        self::assertSame('Draft updated title', $property->getTitle());
        self::assertSame(300000, $property->getPriceByn());
    }

    private function createProperty(int $ownerId, int $propertyId): Property
    {
        $property = new Property(
            ownerId: Id::fromInt($ownerId),
            type: 'apartment',
            dealType: 'sale',
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
            maxDailyGuests: null,
            dailySingleBeds: null,
            dailyDoubleBeds: null,
            checkInTime: null,
            checkOutTime: null,
            address: Address::create('15', null),
            cityId: 1,
            coordinates: Coordinates::create(53.9045, 27.5615),
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

    private function createMetroCalculator(
        PropertyMetroStationRepositoryInterface $propertyMetroStationRepository
    ): MetroProximityCalculator {
        $metroStationRepository = $this->createStub(MetroStationRepositoryInterface::class);
        $metroStationRepository->method('findAll')->willReturn([]);

        return new MetroProximityCalculator($metroStationRepository, $propertyMetroStationRepository);
    }
}
