<?php

declare(strict_types=1);

namespace App\Tests\Unit\Domain\Property\Entity;

use App\Domain\Property\Entity\Property;
use App\Domain\Property\ValueObject\Address;
use App\Domain\Property\ValueObject\Coordinates;
use App\Domain\Property\ValueObject\Price;
use App\Domain\Shared\Exception\DomainException;
use App\Domain\Shared\ValueObject\Id;
use PHPUnit\Framework\TestCase;

final class PropertyTest extends TestCase
{
    public function testPublishMovesDraftToModeration(): void
    {
        $property = $this->createProperty();

        $property->publish();

        self::assertSame('moderation', $property->getStatus());
    }

    public function testApproveMovesModerationToPublished(): void
    {
        $property = $this->createProperty();
        $property->publish();

        $property->approve();

        self::assertSame('published', $property->getStatus());
        self::assertNotNull($property->getPublishedAt());
    }

    public function testRejectMovesModerationToRejected(): void
    {
        $property = $this->createProperty();
        $property->publish();

        $property->reject('Needs better photos');

        self::assertSame('rejected', $property->getStatus());
        self::assertSame('Needs better photos', $property->getModerationComment());
    }

    public function testArchiveMovesPublishedToArchived(): void
    {
        $property = $this->createProperty();
        $property->publish();
        $property->approve();

        $property->archive();

        self::assertSame('archived', $property->getStatus());
    }

    public function testDeleteMarksPropertyAsDeleted(): void
    {
        $property = $this->createProperty();

        $property->delete();

        self::assertSame('deleted', $property->getStatus());
    }

    public function testIsOwnedByChecksOwnerIdentity(): void
    {
        $property = $this->createProperty(ownerId: 123);

        self::assertTrue($property->isOwnedBy('123'));
        self::assertFalse($property->isOwnedBy('321'));
    }

    public function testApproveFromDraftThrowsDomainException(): void
    {
        $property = $this->createProperty();

        $this->expectException(DomainException::class);
        $this->expectExceptionMessage('Одобрить можно только объявления на модерации');

        $property->approve();
    }

    private function createProperty(int $ownerId = 1): Property
    {
        return new Property(
            ownerId: Id::fromInt($ownerId),
            type: 'apartment',
            dealType: 'sale',
            title: 'Spacious apartment in city center',
            description: 'Nice and bright apartment with good location and transport access.',
            price: Price::fromAmount(12000000, 'BYN'),
            area: 80.0,
            rooms: 3,
            floor: 4,
            totalFloors: 9,
            bathrooms: 1,
            yearBuilt: 2010,
            renovation: null,
            balcony: null,
            livingArea: null,
            kitchenArea: null,
            dealConditions: null,
            maxDailyGuests: null,
            dailyBedCount: null,
            checkInTime: null,
            checkOutTime: null,
            address: Address::create('10A', null),
            cityId: 1,
            coordinates: Coordinates::create(53.9045, 27.5615),
        );
    }
}
