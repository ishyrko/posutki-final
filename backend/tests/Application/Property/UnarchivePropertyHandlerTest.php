<?php

declare(strict_types=1);

namespace App\Tests\Application\Property;

use App\Application\Command\Property\UnarchiveProperty\UnarchivePropertyCommand;
use App\Application\Command\Property\UnarchiveProperty\UnarchivePropertyHandler;
use App\Domain\Property\Entity\Property;
use App\Domain\Property\Repository\PropertyRepositoryInterface;
use App\Domain\Property\ValueObject\Address;
use App\Domain\Property\ValueObject\Coordinates;
use App\Domain\Property\ValueObject\Price;
use App\Domain\Shared\Exception\DomainException;
use App\Domain\Shared\ValueObject\Id;
use PHPUnit\Framework\TestCase;

final class UnarchivePropertyHandlerTest extends TestCase
{
    public function testUnarchivesPropertyForOwner(): void
    {
        $property = $this->createProperty(ownerId: 7);
        $property->setStatus('published');
        $property->archive();

        $repository = $this->createMock(PropertyRepositoryInterface::class);
        $repository->method('findById')->willReturn($property);
        $repository->expects(self::once())->method('save')->with($property);

        $handler = new UnarchivePropertyHandler($repository);
        $handler(new UnarchivePropertyCommand('11', '7'));

        self::assertSame('published', $property->getStatus());
        self::assertNull($property->getArchivedAt());
    }

    public function testUnarchiveByOtherUserThrowsDomainException(): void
    {
        $property = $this->createProperty(ownerId: 7);
        $property->setStatus('published');
        $property->archive();

        $repository = $this->createStub(PropertyRepositoryInterface::class);
        $repository->method('findById')->willReturn($property);

        $handler = new UnarchivePropertyHandler($repository);

        $this->expectException(DomainException::class);
        $this->expectExceptionMessage('Нет прав на активацию этого объявления');

        $handler(new UnarchivePropertyCommand('11', '2'));
    }

    public function testUnarchivePublishedThrowsFromEntity(): void
    {
        $property = $this->createProperty(ownerId: 7);
        $property->setStatus('published');

        $repository = $this->createStub(PropertyRepositoryInterface::class);
        $repository->method('findById')->willReturn($property);

        $handler = new UnarchivePropertyHandler($repository);

        $this->expectException(DomainException::class);
        $this->expectExceptionMessage('Активировать можно только скрытое объявление');

        $handler(new UnarchivePropertyCommand('11', '7'));
    }

    private function createProperty(int $ownerId): Property
    {
        $property = new Property(
            ownerId: Id::fromInt($ownerId),
            type: 'apartment',
            dealType: 'daily',
            title: 'Unarchive handler test listing',
            description: 'Unarchive handler test listing description with enough length.',
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
        );

        $idReflection = new \ReflectionProperty($property, 'id');
        $idReflection->setAccessible(true);
        $idReflection->setValue($property, Id::fromInt(11));

        return $property;
    }
}
