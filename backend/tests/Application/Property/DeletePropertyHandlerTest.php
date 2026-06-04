<?php

declare(strict_types=1);

namespace App\Tests\Application\Property;

use App\Application\Command\Property\DeleteProperty\DeletePropertyCommand;
use App\Application\Command\Property\DeleteProperty\DeletePropertyHandler;
use App\Domain\Property\Entity\Property;
use App\Domain\Property\Repository\PropertyRepositoryInterface;
use App\Domain\Property\ValueObject\Address;
use App\Domain\Property\ValueObject\Coordinates;
use App\Domain\Property\ValueObject\Price;
use App\Domain\Shared\Exception\DomainException;
use App\Domain\Shared\Exception\UnauthorizedException;
use App\Domain\Shared\ValueObject\Id;
use PHPUnit\Framework\TestCase;

final class DeletePropertyHandlerTest extends TestCase
{
    public function testDeletesDraftWithoutPublishedAt(): void
    {
        $property = $this->createProperty(ownerId: 3);

        $repository = $this->createMock(PropertyRepositoryInterface::class);
        $repository->method('findById')->willReturn($property);
        $repository->expects(self::once())->method('save')->with($property);

        $handler = new DeletePropertyHandler($repository);
        $handler(new DeletePropertyCommand('12', '3'));

        self::assertSame('deleted', $property->getStatus());
    }

    public function testDeletePublishedWithoutArchiveThrows(): void
    {
        $property = $this->createProperty(ownerId: 3);
        $property->setStatus('published');

        $repository = $this->createStub(PropertyRepositoryInterface::class);
        $repository->method('findById')->willReturn($property);

        $handler = new DeletePropertyHandler($repository);

        $this->expectException(DomainException::class);
        $this->expectExceptionMessage('Сначала скройте объявление перед удалением');

        $handler(new DeletePropertyCommand('12', '3'));
    }

    public function testDeleteArchivedTooSoonThrows(): void
    {
        $property = $this->createProperty(ownerId: 3);
        $property->setStatus('published');
        $property->archive();

        $repository = $this->createStub(PropertyRepositoryInterface::class);
        $repository->method('findById')->willReturn($property);

        $handler = new DeletePropertyHandler($repository);

        $this->expectException(DomainException::class);
        $this->expectExceptionMessage('Удаление будет доступно через');

        $handler(new DeletePropertyCommand('12', '3'));
    }

    public function testDeleteArchivedAfter30DaysSucceeds(): void
    {
        $property = $this->createProperty(ownerId: 3);
        $property->setStatus('published');
        $property->archive();

        $archivedAtReflection = new \ReflectionProperty($property, 'archivedAt');
        $archivedAtReflection->setAccessible(true);
        $archivedAtReflection->setValue($property, new \DateTimeImmutable('-31 days'));

        $repository = $this->createMock(PropertyRepositoryInterface::class);
        $repository->method('findById')->willReturn($property);
        $repository->expects(self::once())->method('save')->with($property);

        $handler = new DeletePropertyHandler($repository);
        $handler(new DeletePropertyCommand('12', '3'));

        self::assertSame('deleted', $property->getStatus());
    }

    public function testDeleteByOtherUserThrowsUnauthorized(): void
    {
        $property = $this->createProperty(ownerId: 3);

        $repository = $this->createStub(PropertyRepositoryInterface::class);
        $repository->method('findById')->willReturn($property);

        $handler = new DeletePropertyHandler($repository);

        $this->expectException(UnauthorizedException::class);
        $this->expectExceptionMessage('Нет прав на удаление этого объявления');

        $handler(new DeletePropertyCommand('12', '99'));
    }

    private function createProperty(int $ownerId): Property
    {
        $property = new Property(
            ownerId: Id::fromInt($ownerId),
            type: 'apartment',
            dealType: 'daily',
            title: 'Delete handler test listing',
            description: 'Delete handler test listing description with enough length.',
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
        $idReflection->setValue($property, Id::fromInt(12));

        return $property;
    }
}
