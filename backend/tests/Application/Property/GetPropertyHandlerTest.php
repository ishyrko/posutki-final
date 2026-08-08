<?php

declare(strict_types=1);

namespace App\Tests\Application\Property;

use App\Application\Service\IcsCalendarService;
use App\Application\Service\PropertyCalendarAggregator;
use App\Application\Query\Property\GetProperty\GetPropertyHandler;
use App\Application\Query\Property\GetProperty\GetPropertyQuery;
use App\Application\Service\PropertyOwnerPublicContactResolver;
use App\Domain\Property\Entity\City;
use App\Domain\Property\Entity\Property;
use App\Domain\Property\Repository\CityRepositoryInterface;
use App\Domain\Property\Repository\CityDistrictRepositoryInterface;
use App\Domain\Property\Repository\MetroStationRepositoryInterface;
use App\Domain\Property\Repository\PropertyMetroStationRepositoryInterface;
use App\Domain\Property\Repository\PropertyAvailabilityBlockRepositoryInterface;
use App\Domain\Property\Repository\PropertyLandmarkRepositoryInterface;
use App\Domain\Property\Repository\LandmarkRepositoryInterface;
use App\Domain\Property\Repository\PropertyRepositoryInterface;
use App\Domain\Property\Repository\StreetRepositoryInterface;
use App\Domain\Property\ValueObject\Address;
use App\Domain\Property\ValueObject\Coordinates;
use App\Domain\Property\ValueObject\Price;
use App\Domain\Review\Repository\ReviewRepositoryInterface;
use App\Domain\Shared\Exception\NotFoundException;
use App\Domain\Shared\ValueObject\Id;
use App\Domain\User\Repository\UserBusinessProfileRepositoryInterface;
use App\Domain\User\Repository\UserIndividualProfileRepositoryInterface;
use PHPUnit\Framework\TestCase;
use Symfony\Component\HttpClient\MockHttpClient;

final class GetPropertyHandlerTest extends TestCase
{
    public function testArchivedPropertyReturnsNotFoundForGuest(): void
    {
        $property = $this->createProperty(ownerId: 4);
        $property->setStatus('published');
        $property->archive();

        $handler = $this->createHandler($property);

        $this->expectException(NotFoundException::class);
        $this->expectExceptionMessage('Объявление не найдено');

        $handler(new GetPropertyQuery('20', null));
    }

    public function testArchivedPropertyReturnsDtoForOwner(): void
    {
        $property = $this->createProperty(ownerId: 4);
        $property->setStatus('published');
        $property->archive();

        $handler = $this->createHandler($property);
        $dto = $handler(new GetPropertyQuery('20', '4'));

        self::assertSame(20, $dto->id);
        self::assertSame('archived', $dto->status);
    }

    public function testDeletedPropertyReturnsDtoForOwner(): void
    {
        $property = $this->createProperty(ownerId: 4);
        $property->delete();

        $handler = $this->createHandler($property);
        $dto = $handler(new GetPropertyQuery('20', '4'));

        self::assertSame(20, $dto->id);
        self::assertSame('deleted', $dto->status);
    }

    public function testDraftPropertyReturnsDtoForOwner(): void
    {
        $property = $this->createProperty(ownerId: 4);

        $handler = $this->createHandler($property);
        $dto = $handler(new GetPropertyQuery('20', '4'));

        self::assertSame(20, $dto->id);
        self::assertSame('draft', $dto->status);
    }

    public function testFreePlacementTruncatesMediaForGuestButNotForOwner(): void
    {
        $images = [
            'https://example.com/1.jpg',
            'https://example.com/2.jpg',
            'https://example.com/3.jpg',
            'https://example.com/4.jpg',
            'https://example.com/5.jpg',
            'https://example.com/6.jpg',
            'https://example.com/7.jpg',
        ];
        $videoUrl = 'https://youtu.be/quYKZushRPo';
        $instagramUrl = 'https://instagram.com/example';
        $websiteUrl = 'https://example.com';

        $property = $this->createProperty(
            ownerId: 4,
            images: $images,
            videoUrl: $videoUrl,
            instagramUrl: $instagramUrl,
            websiteUrl: $websiteUrl,
        );
        // No free VIP 1 — stay on free placement (effective level 0).
        $property->setStatus('published', grantFreeTrial: false);

        $handler = $this->createHandler($property);

        $guestDto = $handler(new GetPropertyQuery('20', null));
        self::assertCount(5, $guestDto->images);
        self::assertNull($guestDto->videoUrl);
        self::assertNull($guestDto->instagramUrl);
        self::assertNull($guestDto->websiteUrl);

        $ownerDto = $handler(new GetPropertyQuery('20', '4'));
        self::assertCount(7, $ownerDto->images);
        self::assertSame($videoUrl, $ownerDto->videoUrl);
        self::assertSame($instagramUrl, $ownerDto->instagramUrl);
        self::assertSame($websiteUrl, $ownerDto->websiteUrl);
    }

    private function createHandler(Property $property): GetPropertyHandler
    {
        $propertyRepository = $this->createMock(PropertyRepositoryInterface::class);
        $propertyRepository->method('findById')->willReturn($property);
        $propertyRepository->expects(self::never())->method('save');

        $city = new City();
        $nameReflection = new \ReflectionProperty($city, 'name');
        $nameReflection->setAccessible(true);
        $nameReflection->setValue($city, 'Minsk');
        $slugReflection = new \ReflectionProperty($city, 'slug');
        $slugReflection->setAccessible(true);
        $slugReflection->setValue($city, 'minsk');
        $idReflection = new \ReflectionProperty($city, 'id');
        $idReflection->setAccessible(true);
        $idReflection->setValue($city, 1);

        $cityRepository = $this->createStub(CityRepositoryInterface::class);
        $cityRepository->method('findById')->willReturn($city);

        $cityDistrictRepository = $this->createStub(CityDistrictRepositoryInterface::class);
        $streetRepository = $this->createStub(StreetRepositoryInterface::class);
        $propertyMetroStationRepository = $this->createStub(PropertyMetroStationRepositoryInterface::class);
        $propertyMetroStationRepository->method('findByPropertyIds')->willReturn([]);
        $propertyLandmarkRepository = $this->createStub(PropertyLandmarkRepositoryInterface::class);
        $propertyLandmarkRepository->method('findByPropertyId')->willReturn([]);
        $landmarkRepository = $this->createStub(LandmarkRepositoryInterface::class);
        $landmarkRepository->method('findActiveByIds')->willReturn([]);
        $metroStationRepository = $this->createStub(MetroStationRepositoryInterface::class);
        $metroStationRepository->method('findByIds')->willReturn([]);

        $userIndividualProfileRepository = $this->createStub(UserIndividualProfileRepositoryInterface::class);
        $userBusinessProfileRepository = $this->createStub(UserBusinessProfileRepositoryInterface::class);

        $ownerContactResolver = $this->createStub(PropertyOwnerPublicContactResolver::class);
        $ownerContactResolver->method('resolveForOwnerIds')->willReturn([
            4 => ['phone' => null, 'name' => null, 'phones' => [], 'telegram' => null],
        ]);

        $reviewRepository = $this->createStub(ReviewRepositoryInterface::class);
        $reviewRepository->method('getAggregateByPropertyId')->willReturn(['avg' => null, 'count' => 0]);
        $reviewRepository->method('findByAuthorAndProperty')->willReturn(null);

        $availabilityBlockRepository = $this->createStub(PropertyAvailabilityBlockRepositoryInterface::class);
        $availabilityBlockRepository->method('findLatestCreatedAtByPropertyId')->willReturn(null);
        $propertyCalendarAggregator = new PropertyCalendarAggregator(
            $availabilityBlockRepository,
            $propertyRepository,
            new IcsCalendarService(new MockHttpClient()),
        );

        return new GetPropertyHandler(
            $propertyRepository,
            $cityRepository,
            $cityDistrictRepository,
            $streetRepository,
            $metroStationRepository,
            $propertyMetroStationRepository,
            $propertyLandmarkRepository,
            $landmarkRepository,
            $userIndividualProfileRepository,
            $userBusinessProfileRepository,
            $ownerContactResolver,
            $reviewRepository,
            $propertyCalendarAggregator,
        );
    }

    private function createProperty(
        int $ownerId,
        array $images = [],
        ?string $videoUrl = null,
        ?string $instagramUrl = null,
        ?string $websiteUrl = null,
    ): Property
    {
        $property = new Property(
            ownerId: Id::fromInt($ownerId),
            type: 'apartment',
            dealType: 'daily',
            title: 'Get property handler test listing',
            description: 'Get property handler test listing description with enough length.',
            price: Price::fromAmount(10000000, 'BYN'),
            area: 60.0,
            rooms: 2,
            floor: 3,
            totalFloors: 9,
            bathrooms: 1,
            yearBuilt: 2015,
            renovation: null,
            balcony: null,
            livingArea: null,
            kitchenArea: null,
            dealConditions: null,
            paymentMethods: null,
            maxDailyGuests: 4,
            dailySingleBeds: null,
            dailyDoubleBeds: null,
            checkInTime: null,
            checkOutTime: null,
            address: Address::create('1', null),
            cityId: 1,
            coordinates: Coordinates::create(53.9, 27.56),
            images: $images,
            instagramUrl: $instagramUrl,
            websiteUrl: $websiteUrl,
            videoUrl: $videoUrl,
        );

        $idReflection = new \ReflectionProperty($property, 'id');
        $idReflection->setAccessible(true);
        $idReflection->setValue($property, Id::fromInt(20));

        return $property;
    }
}
