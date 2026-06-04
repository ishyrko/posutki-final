<?php

declare(strict_types=1);

namespace App\Tests\Application\Property;

use App\Application\Command\Property\ArchiveProperty\ArchivePropertyCommand;
use App\Application\Command\Property\ArchiveProperty\ArchivePropertyHandler;
use App\Domain\Property\Entity\Property;
use App\Domain\Property\Repository\PropertyRepositoryInterface;
use App\Domain\Property\ValueObject\Address;
use App\Domain\Property\ValueObject\Coordinates;
use App\Domain\Property\ValueObject\Price;
use App\Domain\Shared\Exception\DomainException;
use App\Domain\Shared\ValueObject\Id;
use PHPUnit\Framework\TestCase;

final class ArchivePropertyHandlerTest extends TestCase
{
    public function testArchivesPublishedPropertyForOwner(): void
    {
        $property = $this->createProperty(ownerId: 5);
        $property->setStatus('published');

        $repository = $this->createMock(PropertyRepositoryInterface::class);
        $repository->method('findById')->willReturn($property);
        $repository->expects(self::once())->method('save')->with($property);

        $handler = new ArchivePropertyHandler($repository);
        $archivedAt = $handler(new ArchivePropertyCommand('10', '5'));

        self::assertSame('archived', $property->getStatus());
        self::assertNotNull($property->getArchivedAt());
        self::assertSame($property->getArchivedAt()->format('c'), $archivedAt);
    }

    public function testArchiveDraftThrowsDomainException(): void
    {
        $property = $this->createProperty(ownerId: 5);

        $repository = $this->createStub(PropertyRepositoryInterface::class);
        $repository->method('findById')->willReturn($property);

        $handler = new ArchivePropertyHandler($repository);

        $this->expectException(DomainException::class);
        $this->expectExceptionMessage('Скрыть можно только опубликованное объявление');

        $handler(new ArchivePropertyCommand('10', '5'));
    }

    public function testArchiveByOtherUserThrowsDomainException(): void
    {
        $property = $this->createProperty(ownerId: 5);
        $property->setStatus('published');

        $repository = $this->createStub(PropertyRepositoryInterface::class);
        $repository->method('findById')->willReturn($property);

        $handler = new ArchivePropertyHandler($repository);

        $this->expectException(DomainException::class);
        $this->expectExceptionMessage('Нет прав на скрытие этого объявления');

        $handler(new ArchivePropertyCommand('10', '99'));
    }

    private function createProperty(int $ownerId): Property
    {
        $property = new Property(
            ownerId: Id::fromInt($ownerId),
            type: 'apartment',
            dealType: 'daily',
            title: 'Archive handler test listing',
            description: 'Archive handler test listing description with enough length.',
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
        $idReflection->setValue($property, Id::fromInt(10));

        return $property;
    }
}
