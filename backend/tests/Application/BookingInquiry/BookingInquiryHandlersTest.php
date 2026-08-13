<?php

declare(strict_types=1);

namespace App\Tests\Application\BookingInquiry;

use App\Application\Command\BookingInquiry\Accept\AcceptBookingInquiryCommand;
use App\Application\Command\BookingInquiry\Accept\AcceptBookingInquiryHandler;
use App\Application\Command\BookingInquiry\Decline\DeclineBookingInquiryCommand;
use App\Application\Command\BookingInquiry\Decline\DeclineBookingInquiryHandler;
use App\Application\Command\BookingInquiry\Reply\ReplyToBookingInquiryCommand;
use App\Application\Command\BookingInquiry\Reply\ReplyToBookingInquiryHandler;
use App\Application\Command\BookingInquiry\Submit\SubmitBookingInquiryCommand;
use App\Application\Command\BookingInquiry\Submit\SubmitBookingInquiryHandler;
use App\Application\Command\CommandBusInterface;
use App\Application\Command\Property\CreateAvailabilityBlock\CreateAvailabilityBlockCommand;
use App\Application\Service\IcsCalendarService;
use App\Application\Service\PropertyCalendarAggregator;
use App\Domain\BookingInquiry\Entity\BookingInquiry;
use App\Domain\BookingInquiry\Event\BookingInquiryRepliedEvent;
use App\Domain\BookingInquiry\Repository\BookingInquiryRepositoryInterface;
use App\Domain\BookingInquiry\ValueObject\BookingInquiryStatus;
use App\Domain\Property\Entity\Property;
use App\Domain\Property\Entity\PropertyAvailabilityBlock;
use App\Domain\Property\Repository\PropertyAvailabilityBlockRepositoryInterface;
use App\Domain\Property\Repository\PropertyRepositoryInterface;
use App\Domain\Shared\Exception\DomainException;
use App\Domain\Shared\ValueObject\Id;
use App\Domain\User\Entity\User;
use App\Domain\User\Repository\UserRepositoryInterface;
use App\Domain\User\ValueObject\Email;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Messenger\Envelope;
use Symfony\Component\Messenger\MessageBusInterface;
use Symfony\Contracts\HttpClient\HttpClientInterface;

final class BookingInquiryHandlersTest extends TestCase
{
    public function testReplySavesAndDispatchesEvent(): void
    {
        $inquiry = $this->createInquiry(email: 'guest@example.com');
        $this->setEntityId($inquiry, 1);

        $repository = $this->createMock(BookingInquiryRepositoryInterface::class);
        $repository->method('findById')->willReturn($inquiry);
        $repository->expects(self::once())->method('save')->with($inquiry);

        $notificationBus = $this->createMock(MessageBusInterface::class);
        $notificationBus
            ->expects(self::once())
            ->method('dispatch')
            ->with(self::callback(static function (object $event): bool {
                return $event instanceof BookingInquiryRepliedEvent
                    && $event->inquiryId === '1'
                    && $event->guestEmail === 'guest@example.com';
            }))
            ->willReturn(new Envelope(new \stdClass()));

        $handler = new ReplyToBookingInquiryHandler(
            $repository,
            $notificationBus,
        );

        $result = ($handler)(new ReplyToBookingInquiryCommand(
            inquiryId: '1',
            ownerId: '10',
            text: 'Даты свободны',
        ));

        self::assertSame('replied', $result['status']);
        self::assertSame('Даты свободны', $inquiry->getOwnerReply());
        self::assertSame(BookingInquiryStatus::Replied, $inquiry->getStatus());
        self::assertCount(1, $inquiry->getOwnerReplies());
        self::assertSame('Даты свободны', $inquiry->getOwnerReplies()[0]['text']);
    }

    public function testReplyAppendsToHistory(): void
    {
        $inquiry = $this->createInquiry(email: 'guest@example.com');
        $this->setEntityId($inquiry, 1);
        $inquiry->reply('Первый ответ');

        $repository = $this->createMock(BookingInquiryRepositoryInterface::class);
        $repository->method('findById')->willReturn($inquiry);
        $repository->expects(self::once())->method('save')->with($inquiry);

        $notificationBus = $this->createMock(MessageBusInterface::class);
        $notificationBus
            ->expects(self::once())
            ->method('dispatch')
            ->willReturn(new Envelope(new \stdClass()));

        $handler = new ReplyToBookingInquiryHandler(
            $repository,
            $notificationBus,
        );

        ($handler)(new ReplyToBookingInquiryCommand(
            inquiryId: '1',
            ownerId: '10',
            text: 'Второй ответ',
        ));

        self::assertSame('Второй ответ', $inquiry->getOwnerReply());
        self::assertCount(2, $inquiry->getOwnerReplies());
        self::assertSame('Первый ответ', $inquiry->getOwnerReplies()[0]['text']);
        self::assertSame('Второй ответ', $inquiry->getOwnerReplies()[1]['text']);
    }

    public function testReplyKeepsAcceptedStatus(): void
    {
        $inquiry = $this->createInquiry(email: 'guest@example.com');
        $this->setEntityId($inquiry, 1);
        $inquiry->accept(Id::fromInt(50));

        $repository = $this->createMock(BookingInquiryRepositoryInterface::class);
        $repository->method('findById')->willReturn($inquiry);
        $repository->expects(self::once())->method('save')->with($inquiry);

        $notificationBus = $this->createMock(MessageBusInterface::class);
        $notificationBus
            ->expects(self::once())
            ->method('dispatch')
            ->willReturn(new Envelope(new \stdClass()));

        $handler = new ReplyToBookingInquiryHandler(
            $repository,
            $notificationBus,
        );

        $result = ($handler)(new ReplyToBookingInquiryCommand(
            inquiryId: '1',
            ownerId: '10',
            text: 'Ждём вас',
        ));

        self::assertSame('accepted', $result['status']);
        self::assertSame('Ждём вас', $inquiry->getOwnerReply());
        self::assertSame(BookingInquiryStatus::Accepted, $inquiry->getStatus());
    }

    public function testReplyFailsWithoutGuestEmail(): void
    {
        $inquiry = $this->createInquiry(email: null);
        $this->setEntityId($inquiry, 1);

        $repository = $this->createStub(BookingInquiryRepositoryInterface::class);
        $repository->method('findById')->willReturn($inquiry);

        $handler = new ReplyToBookingInquiryHandler(
            $repository,
            $this->createStub(MessageBusInterface::class),
        );

        $this->expectException(DomainException::class);
        $this->expectExceptionMessage('У гостя не указан email');

        ($handler)(new ReplyToBookingInquiryCommand(
            inquiryId: '1',
            ownerId: '10',
            text: 'Ответ',
        ));
    }

    public function testReplyFailsForRegisteredGuest(): void
    {
        $inquiry = new BookingInquiry(
            propertyId: Id::fromInt(100),
            ownerId: Id::fromInt(10),
            name: 'Иван',
            phone: '+375291112233',
            userId: Id::fromInt(5),
            email: 'guest@example.com',
        );
        $this->setEntityId($inquiry, 1);

        $repository = $this->createStub(BookingInquiryRepositoryInterface::class);
        $repository->method('findById')->willReturn($inquiry);

        $handler = new ReplyToBookingInquiryHandler(
            $repository,
            $this->createStub(MessageBusInterface::class),
        );

        $this->expectException(DomainException::class);
        $this->expectExceptionMessage('Для зарегистрированных гостей используйте диалоги');

        ($handler)(new ReplyToBookingInquiryCommand(
            inquiryId: '1',
            ownerId: '10',
            text: 'Ответ',
        ));
    }

    public function testAcceptCreatesBlockWithCheckoutMinusOneDay(): void
    {
        $inquiry = new BookingInquiry(
            propertyId: Id::fromInt(100),
            ownerId: Id::fromInt(10),
            name: 'Иван',
            phone: '+375291112233',
            checkIn: new \DateTimeImmutable('2026-08-15'),
            checkOut: new \DateTimeImmutable('2026-08-18'),
            notes: 'Нужна парковка',
        );
        $this->setEntityId($inquiry, 1);

        $propertyRepository = $this->createStub(PropertyRepositoryInterface::class);
        $propertyRepository->method('findById')->willReturn($this->createPropertyStub());

        $commandBus = $this->createMock(CommandBusInterface::class);
        $commandBus
            ->expects(self::once())
            ->method('dispatch')
            ->with(self::callback(static function (object $command): bool {
                return $command instanceof CreateAvailabilityBlockCommand
                    && $command->startDate === '2026-08-15'
                    && $command->endDate === '2026-08-17'
                    && str_starts_with((string) $command->note, 'BOOKED:Иван');
            }))
            ->willReturn(['id' => '55']);

        $repository = $this->createMock(BookingInquiryRepositoryInterface::class);
        $repository->method('findById')->willReturn($inquiry);
        $repository->expects(self::once())->method('save')->with($inquiry);

        $handler = new AcceptBookingInquiryHandler(
            $repository,
            $propertyRepository,
            $this->createCalendarAggregator([]),
            $commandBus,
        );

        $result = ($handler)(new AcceptBookingInquiryCommand(
            inquiryId: '1',
            ownerId: '10',
        ));

        self::assertSame('accepted', $result['status']);
        self::assertSame('55', $result['availabilityBlockId']);
        self::assertSame(BookingInquiryStatus::Accepted, $inquiry->getStatus());
    }

    public function testAcceptWithoutCalendarBooking(): void
    {
        $inquiry = new BookingInquiry(
            propertyId: Id::fromInt(100),
            ownerId: Id::fromInt(10),
            name: 'Иван',
            phone: '+375291112233',
            checkIn: new \DateTimeImmutable('2026-08-15'),
            checkOut: new \DateTimeImmutable('2026-08-18'),
        );
        $this->setEntityId($inquiry, 1);

        $propertyRepository = $this->createStub(PropertyRepositoryInterface::class);
        $propertyRepository->method('findById')->willReturn($this->createPropertyStub());

        $commandBus = $this->createMock(CommandBusInterface::class);
        $commandBus->expects(self::never())->method('dispatch');

        $repository = $this->createMock(BookingInquiryRepositoryInterface::class);
        $repository->method('findById')->willReturn($inquiry);
        $repository->expects(self::once())->method('save')->with($inquiry);

        $handler = new AcceptBookingInquiryHandler(
            $repository,
            $propertyRepository,
            $this->createCalendarAggregator([]),
            $commandBus,
        );

        $result = ($handler)(new AcceptBookingInquiryCommand(
            inquiryId: '1',
            ownerId: '10',
            bookCalendar: false,
        ));

        self::assertSame('accepted', $result['status']);
        self::assertNull($result['availabilityBlockId']);
        self::assertSame(BookingInquiryStatus::Accepted, $inquiry->getStatus());
        self::assertNull($inquiry->getAvailabilityBlockId());
    }

    public function testAcceptFailsWhenDatesOverlap(): void
    {
        $inquiry = new BookingInquiry(
            propertyId: Id::fromInt(100),
            ownerId: Id::fromInt(10),
            name: 'Иван',
            phone: '+375291112233',
            checkIn: new \DateTimeImmutable('2026-08-15'),
            checkOut: new \DateTimeImmutable('2026-08-18'),
        );
        $this->setEntityId($inquiry, 1);

        $propertyRepository = $this->createStub(PropertyRepositoryInterface::class);
        $propertyRepository->method('findById')->willReturn($this->createPropertyStub());

        $occupiedBlock = new PropertyAvailabilityBlock(
            propertyId: Id::fromInt(100),
            startDate: new \DateTimeImmutable('2026-08-16'),
            endDate: new \DateTimeImmutable('2026-08-16'),
        );

        $repository = $this->createStub(BookingInquiryRepositoryInterface::class);
        $repository->method('findById')->willReturn($inquiry);

        $handler = new AcceptBookingInquiryHandler(
            $repository,
            $propertyRepository,
            $this->createCalendarAggregator([$occupiedBlock]),
            $this->createStub(CommandBusInterface::class),
        );

        $this->expectException(DomainException::class);
        $this->expectExceptionMessage('Выбранные даты уже заняты в календаре');

        ($handler)(new AcceptBookingInquiryCommand(
            inquiryId: '1',
            ownerId: '10',
        ));
    }

    public function testDeclineRemovesAvailabilityBlockWhenPresent(): void
    {
        $inquiry = new BookingInquiry(
            propertyId: Id::fromInt(100),
            ownerId: Id::fromInt(10),
            name: 'Иван',
            phone: '+375291112233',
            checkIn: new \DateTimeImmutable('2026-08-15'),
        );
        $this->setEntityId($inquiry, 1);
        $inquiry->accept(Id::fromInt(55));

        $repository = $this->createMock(BookingInquiryRepositoryInterface::class);
        $repository->method('findById')->willReturn($inquiry);
        $repository->expects(self::once())->method('save')->with($inquiry);

        $commandBus = $this->createMock(CommandBusInterface::class);
        $commandBus->expects(self::once())->method('dispatch');

        $handler = new DeclineBookingInquiryHandler($repository, $commandBus);

        $result = ($handler)(new DeclineBookingInquiryCommand(
            inquiryId: '1',
            ownerId: '10',
        ));

        self::assertSame('declined', $result['status']);
        self::assertSame(BookingInquiryStatus::Declined, $inquiry->getStatus());
        self::assertNull($inquiry->getAvailabilityBlockId());
    }

    public function testSubmitFailsWhenOwnerDisabledMessagesAndInquiries(): void
    {
        $property = $this->createPropertyStub();
        $property->method('getOwnerId')->willReturn(Id::fromInt(10));

        $propertyRepository = $this->createStub(PropertyRepositoryInterface::class);
        $propertyRepository->method('findById')->willReturn($property);

        $owner = User::register(Email::fromString('owner@example.com'), '', 'Owner', 'User');
        $owner->verify();
        $owner->setAllowMessagesAndInquiries(false);

        $userRepository = $this->createStub(UserRepositoryInterface::class);
        $userRepository->method('findById')->willReturn($owner);

        $handler = new SubmitBookingInquiryHandler(
            $this->createStub(BookingInquiryRepositoryInterface::class),
            $propertyRepository,
            $userRepository,
            $this->createStub(MessageBusInterface::class),
            $this->createCalendarAggregator([]),
        );

        $this->expectException(DomainException::class);
        $this->expectExceptionMessage('Владелец отключил приём сообщений и заявок на бронирование');

        ($handler)(new SubmitBookingInquiryCommand(
            propertyId: '100',
            name: 'Гость',
            phone: '+375291112233',
            email: 'guest@example.com',
        ));
    }

    public function testSubmitFailsWithoutCheckIn(): void
    {
        $handler = $this->createSubmitHandler(minStayDays: 1);

        $this->expectException(DomainException::class);
        $this->expectExceptionMessage('Укажите дату заезда');

        ($handler)(new SubmitBookingInquiryCommand(
            propertyId: '100',
            name: 'Гость',
            phone: '+375291112233',
            email: 'guest@example.com',
            guests: 2,
            checkOut: '2026-08-18',
        ));
    }

    public function testSubmitFailsWithoutCheckOut(): void
    {
        $handler = $this->createSubmitHandler(minStayDays: 1);

        $this->expectException(DomainException::class);
        $this->expectExceptionMessage('Укажите дату выезда');

        ($handler)(new SubmitBookingInquiryCommand(
            propertyId: '100',
            name: 'Гость',
            phone: '+375291112233',
            email: 'guest@example.com',
            guests: 2,
            checkIn: '2026-08-15',
        ));
    }

    public function testSubmitFailsWhenStayShorterThanMinStayDays(): void
    {
        $handler = $this->createSubmitHandler(minStayDays: 3);

        $this->expectException(DomainException::class);
        $this->expectExceptionMessage('Минимальный срок проживания — 3 суток');

        ($handler)(new SubmitBookingInquiryCommand(
            propertyId: '100',
            name: 'Гость',
            phone: '+375291112233',
            email: 'guest@example.com',
            guests: 2,
            checkIn: '2026-08-15',
            checkOut: '2026-08-17',
        ));
    }

    public function testSubmitFailsWhenGuestsExceedMaxDailyGuests(): void
    {
        $handler = $this->createSubmitHandler(minStayDays: 1, maxDailyGuests: 2);

        $this->expectException(DomainException::class);
        $this->expectExceptionMessage('Максимум гостей для этого объявления — 2');

        ($handler)(new SubmitBookingInquiryCommand(
            propertyId: '100',
            name: 'Гость',
            phone: '+375291112233',
            email: 'guest@example.com',
            guests: 3,
            checkIn: '2026-08-15',
            checkOut: '2026-08-17',
        ));
    }

    public function testSubmitSucceedsWithRequiredDates(): void
    {
        $saved = null;
        $repository = $this->createMock(BookingInquiryRepositoryInterface::class);
        $repository
            ->expects(self::once())
            ->method('save')
            ->with(self::callback(function (BookingInquiry $inquiry) use (&$saved): bool {
                $this->setEntityId($inquiry, 42);
                $saved = $inquiry;

                return true;
            }));

        $handler = $this->createSubmitHandler(minStayDays: 2, maxDailyGuests: 4, repository: $repository);

        $result = ($handler)(new SubmitBookingInquiryCommand(
            propertyId: '100',
            name: 'Гость',
            phone: '+375291112233',
            email: 'guest@example.com',
            guests: 2,
            checkIn: '2026-08-15',
            checkOut: '2026-08-17',
        ));

        self::assertSame(['id' => '42'], $result);
        self::assertInstanceOf(BookingInquiry::class, $saved);
        self::assertSame(2, $saved->getGuests());
        self::assertSame('2026-08-15', $saved->getCheckIn()?->format('Y-m-d'));
        self::assertSame('2026-08-17', $saved->getCheckOut()?->format('Y-m-d'));
    }

    private function createSubmitHandler(
        int $minStayDays,
        int $maxDailyGuests = 4,
        ?BookingInquiryRepositoryInterface $repository = null,
    ): SubmitBookingInquiryHandler {
        $property = $this->createPropertyStub($minStayDays, $maxDailyGuests);
        $property->method('getOwnerId')->willReturn(Id::fromInt(10));

        $propertyRepository = $this->createStub(PropertyRepositoryInterface::class);
        $propertyRepository->method('findById')->willReturn($property);

        $owner = User::register(Email::fromString('owner@example.com'), '', 'Owner', 'User');
        $owner->verify();

        $userRepository = $this->createStub(UserRepositoryInterface::class);
        $userRepository->method('findById')->willReturn($owner);

        $notificationBus = $this->createStub(MessageBusInterface::class);
        $notificationBus->method('dispatch')->willReturn(new Envelope(new \stdClass()));

        return new SubmitBookingInquiryHandler(
            $repository ?? $this->createStub(BookingInquiryRepositoryInterface::class),
            $propertyRepository,
            $userRepository,
            $notificationBus,
            $this->createCalendarAggregator([]),
        );
    }

    private function createPropertyStub(int $minStayDays = 1, int $maxDailyGuests = 4): Property
    {
        $property = $this->createStub(Property::class);
        $property->method('getId')->willReturn(Id::fromInt(100));
        $property->method('getDealType')->willReturn('daily');
        $property->method('getMinStayDays')->willReturn($minStayDays);
        $property->method('getMaxDailyGuests')->willReturn($maxDailyGuests);
        $property->method('getExternalCalendarUrls')->willReturn([]);
        $property->method('getExternalCalendarSnapshot')->willReturn(null);
        $property->method('getExternalCalendarSyncedAt')->willReturn(null);

        return $property;
    }

    /**
     * @param list<PropertyAvailabilityBlock> $manualBlocks
     */
    private function createCalendarAggregator(array $manualBlocks): PropertyCalendarAggregator
    {
        $availabilityBlockRepository = $this->createStub(PropertyAvailabilityBlockRepositoryInterface::class);
        $availabilityBlockRepository
            ->method('findByPropertyId')
            ->willReturn($manualBlocks);

        return new PropertyCalendarAggregator(
            $availabilityBlockRepository,
            $this->createStub(PropertyRepositoryInterface::class),
            new IcsCalendarService($this->createStub(HttpClientInterface::class)),
        );
    }

    private function createInquiry(?string $email): BookingInquiry
    {
        return new BookingInquiry(
            propertyId: Id::fromInt(100),
            ownerId: Id::fromInt(10),
            name: 'Иван',
            phone: '+375291112233',
            email: $email,
            checkIn: new \DateTimeImmutable('2026-08-15'),
            checkOut: new \DateTimeImmutable('2026-08-18'),
        );
    }

    private function setEntityId(BookingInquiry $inquiry, int $id): void
    {
        $reflection = new \ReflectionProperty($inquiry, 'id');
        $reflection->setAccessible(true);
        $reflection->setValue($inquiry, Id::fromInt($id));
    }
}
