<?php

declare(strict_types=1);

namespace App\Tests\Application\Property;

use App\Application\Command\Property\RejectRevision\RejectRevisionCommand;
use App\Application\Command\Property\RejectRevision\RejectRevisionHandler;
use App\Domain\Property\Entity\Property;
use App\Domain\Property\Entity\PropertyRevision;
use App\Domain\Property\Event\PropertyRejectedEvent;
use App\Domain\Property\Event\PropertyRevisionRejectedEvent;
use App\Domain\Property\Repository\PropertyRepositoryInterface;
use App\Domain\Property\Repository\PropertyRevisionRepositoryInterface;
use App\Domain\Property\ValueObject\Address;
use App\Domain\Property\ValueObject\Coordinates;
use App\Domain\Property\ValueObject\Price;
use App\Domain\Shared\ValueObject\Id;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Messenger\Envelope;
use Symfony\Component\Messenger\MessageBusInterface;

final class RejectRevisionHandlerTest extends TestCase
{
    public function testRejectingRevisionOfPublishedListingSendsRevisionRejectedEvent(): void
    {
        $property = $this->createProperty();
        $property->setStatus('published');
        $revision = new PropertyRevision($property, ['title' => 'New title']);

        $dispatched = $this->reject($property, $revision, 'Нет фото фасада');

        self::assertSame('published', $property->getStatus());
        self::assertCount(1, $dispatched);
        self::assertInstanceOf(PropertyRevisionRejectedEvent::class, $dispatched[0]);
        self::assertSame('41', $dispatched[0]->propertyId);
        self::assertSame('Нет фото фасада', $dispatched[0]->moderationComment);
    }

    public function testRejectingRevisionOfUnpublishedListingKeepsListingRejectionEvent(): void
    {
        $property = $this->createProperty();
        $property->setStatus('rejected');
        $revision = new PropertyRevision($property, ['title' => 'New title']);

        $dispatched = $this->reject($property, $revision, 'Описание слишком короткое');

        self::assertSame('rejected', $property->getStatus());
        self::assertCount(1, $dispatched);
        self::assertInstanceOf(PropertyRejectedEvent::class, $dispatched[0]);
        self::assertSame('Описание слишком короткое', $dispatched[0]->moderationComment);
    }

    /**
     * @return list<object>
     */
    private function reject(Property $property, PropertyRevision $revision, string $comment): array
    {
        $propertyRepository = $this->createStub(PropertyRepositoryInterface::class);
        $propertyRepository->method('findById')->willReturn($property);

        $revisionRepository = $this->createMock(PropertyRevisionRepositoryInterface::class);
        $revisionRepository->method('findById')->willReturn($revision);
        $revisionRepository->expects(self::once())->method('save')->with($revision);

        $dispatched = new \ArrayObject();
        $handler = new RejectRevisionHandler(
            $propertyRepository,
            $revisionRepository,
            new class($dispatched) implements MessageBusInterface {
                public function __construct(private \ArrayObject $dispatched)
                {
                }

                public function dispatch(object $message, array $stamps = []): Envelope
                {
                    $this->dispatched->append($message);

                    return new Envelope($message);
                }
            },
        );

        $handler(new RejectRevisionCommand(
            propertyId: '41',
            revisionId: '7',
            moderationComment: $comment,
        ));

        return $dispatched->getArrayCopy();
    }

    private function createProperty(): Property
    {
        $property = new Property(
            ownerId: Id::fromInt(1),
            type: 'house',
            dealType: 'daily',
            title: 'Дом у озера',
            description: 'Описание достаточно длинное для тестового объявления.',
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
        $reflection->setValue($property, Id::fromInt(41));

        return $property;
    }
}
