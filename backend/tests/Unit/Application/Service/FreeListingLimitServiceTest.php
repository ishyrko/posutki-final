<?php

declare(strict_types=1);

namespace App\Tests\Unit\Application\Service;

use App\Application\Service\FreeListingLimitService;
use App\Domain\Property\Entity\City;
use App\Domain\Property\Entity\Property;
use App\Domain\Property\Limit\FreeListingLimits;
use App\Domain\Property\Repository\CityRepositoryInterface;
use App\Domain\Property\Repository\PropertyRepositoryInterface;
use App\Domain\Property\ValueObject\Address;
use App\Domain\Property\ValueObject\Coordinates;
use App\Domain\Property\ValueObject\Price;
use App\Domain\Shared\ValueObject\Id;
use App\Domain\User\Entity\User;
use App\Domain\User\Repository\UserRepositoryInterface;
use PHPUnit\Framework\TestCase;

final class FreeListingLimitServiceTest extends TestCase
{
    public function testPartnerIgnoresCityLimitAndUsesAccountCeiling(): void
    {
        $property = $this->createProperty(ownerId: 9);
        $partner = User::registerViaPhone('+375291110000');
        $partner->setIsPartner(true);
        $partner->setPartnerListingLimit(400);

        $propertyRepository = $this->createStub(PropertyRepositoryInterface::class);
        $propertyRepository->method('countFreePublishedByOwner')->willReturn(5);
        $propertyRepository->method('countFreePublishedApartmentsByOwnerInCity')->willReturn(99);

        $city = $this->createStub(City::class);
        $city->method('getFreeApartmentsPerAccount')->willReturn(1);
        $cityRepository = $this->createStub(CityRepositoryInterface::class);
        $cityRepository->method('findById')->willReturn($city);

        $userRepository = $this->createStub(UserRepositoryInterface::class);
        $userRepository->method('findById')->willReturn($partner);

        $service = new FreeListingLimitService($propertyRepository, $cityRepository, $userRepository);

        self::assertTrue($service->canPublishFree($property));
        self::assertFalse($service->isAccountLimitExceeded($property));
        self::assertSame(400, $service->describeLimits($property->getOwnerId(), 1, 'apartment')['account']['limit']);
        self::assertNull($service->describeLimits($property->getOwnerId(), 1, 'apartment')['city']);
    }

    public function testRegularUserHitsAccountLimitOfFive(): void
    {
        $property = $this->createProperty(ownerId: 3);
        $user = User::registerViaPhone('+375291110001');

        $propertyRepository = $this->createStub(PropertyRepositoryInterface::class);
        $propertyRepository->method('countFreePublishedByOwner')->willReturn(5);

        $service = new FreeListingLimitService(
            $propertyRepository,
            $this->createStub(CityRepositoryInterface::class),
            $this->createConfiguredStub(UserRepositoryInterface::class, ['findById' => $user]),
        );

        self::assertFalse($service->canPublishFree($property));
        self::assertTrue($service->isAccountLimitExceeded($property));
        self::assertSame(
            FreeListingLimits::MAX_PUBLISHED_PER_ACCOUNT,
            $service->accountLimitForUser($user),
        );
    }

    private function createProperty(int $ownerId): Property
    {
        $property = new Property(
            ownerId: Id::fromInt($ownerId),
            type: 'apartment',
            dealType: 'daily',
            title: 'Partner listing title here',
            description: 'Partner listing description that is long enough for domain rules.',
            price: Price::fromAmount(100, 'BYN'),
            area: 40.0,
            rooms: 1,
            floor: 2,
            totalFloors: 5,
            bathrooms: 1,
            yearBuilt: 2010,
            renovation: null,
            balcony: null,
            livingArea: null,
            kitchenArea: null,
            dealConditions: null,
            paymentMethods: null,
            maxDailyGuests: 2,
            dailySingleBeds: 2,
            dailyDoubleBeds: 0,
            checkInTime: '14:00',
            checkOutTime: '12:00',
            address: Address::create('1', null),
            cityId: 1,
            coordinates: Coordinates::create(53.9, 27.5),
            images: ['a.jpg', 'b.jpg', 'c.jpg'],
        );

        $idReflection = new \ReflectionProperty($property, 'id');
        $idReflection->setAccessible(true);
        $idReflection->setValue($property, Id::fromInt(77));

        return $property;
    }
}
