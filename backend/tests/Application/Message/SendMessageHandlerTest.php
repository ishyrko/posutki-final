<?php

declare(strict_types=1);

namespace App\Tests\Application\Message;

use App\Application\Command\Message\SendMessage\SendMessageCommand;
use App\Application\Command\Message\SendMessage\SendMessageHandler;
use App\Domain\BookingInquiry\Entity\BookingInquiry;
use App\Domain\BookingInquiry\Repository\BookingInquiryRepositoryInterface;
use App\Domain\Message\Entity\Conversation;
use App\Domain\Message\Entity\Message;
use App\Domain\Message\Event\MessageSentEvent;
use App\Domain\Message\Repository\ConversationRepositoryInterface;
use App\Domain\Message\Repository\MessageRepositoryInterface;
use App\Domain\Property\Entity\Property;
use App\Domain\Property\Repository\PropertyRepositoryInterface;
use App\Domain\Shared\Exception\DomainException;
use App\Domain\Shared\ValueObject\Id;
use App\Domain\User\Entity\User;
use App\Domain\User\Repository\UserRepositoryInterface;
use App\Domain\User\ValueObject\Email;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Messenger\Envelope;
use Symfony\Component\Messenger\MessageBusInterface;

final class SendMessageHandlerTest extends TestCase
{
    public function testSendDispatchesMessageSentEvent(): void
    {
        $conversationRepository = $this->createMock(ConversationRepositoryInterface::class);
        $messageRepository = $this->createMock(MessageRepositoryInterface::class);
        $propertyRepository = $this->createMock(PropertyRepositoryInterface::class);
        $userRepository = $this->createMock(UserRepositoryInterface::class);
        $notificationBus = $this->createMock(MessageBusInterface::class);

        $property = $this->createMock(Property::class);
        $property->method('getOwnerId')->willReturn(Id::fromInt(10));

        $propertyRepository
            ->method('findById')
            ->willReturn($property);

        $owner = User::register(Email::fromString('owner@example.com'), '', 'Owner', 'User');
        $userRepository->method('findById')->willReturn($owner);

        $conversationRepository
            ->expects(self::exactly(2))
            ->method('save')
            ->willReturnCallback(function (Conversation $conversation): void {
                $idReflection = new \ReflectionProperty($conversation, 'id');
                $idReflection->setAccessible(true);
                if (!$idReflection->isInitialized($conversation)) {
                    $idReflection->setValue($conversation, Id::fromInt(42));
                }
            });

        $messageRepository
            ->expects(self::once())
            ->method('save')
            ->willReturnCallback(function (Message $message): void {
                $idReflection = new \ReflectionProperty($message, 'id');
                $idReflection->setAccessible(true);
                if (!$idReflection->isInitialized($message)) {
                    $idReflection->setValue($message, Id::fromInt(99));
                }
            });

        $notificationBus
            ->expects(self::once())
            ->method('dispatch')
            ->with(self::callback(static function (object $event): bool {
                return $event instanceof MessageSentEvent
                    && $event->conversationId === '42'
                    && $event->senderId === '5'
                    && $event->messageText === 'Привет!';
            }))
            ->willReturn(new Envelope(new \stdClass()));

        $handler = $this->createHandler(
            $conversationRepository,
            $messageRepository,
            $propertyRepository,
            $userRepository,
            $notificationBus,
        );

        $handler(new SendMessageCommand(
            senderId: '5',
            propertyId: '100',
            text: 'Привет!',
        ));
    }

    public function testSellerCanStartConversationWithBuyer(): void
    {
        $conversationRepository = $this->createMock(ConversationRepositoryInterface::class);
        $messageRepository = $this->createMock(MessageRepositoryInterface::class);
        $propertyRepository = $this->createMock(PropertyRepositoryInterface::class);
        $userRepository = $this->createMock(UserRepositoryInterface::class);
        $notificationBus = $this->createMock(MessageBusInterface::class);

        $property = $this->createMock(Property::class);
        $property->method('getOwnerId')->willReturn(Id::fromInt(10));
        $propertyRepository->method('findById')->willReturn($property);

        $buyer = User::register(Email::fromString('buyer@example.com'), '', 'Buyer', 'User');
        $userRepository->method('findById')->willReturn($buyer);

        $conversationRepository->method('findByPropertyAndBuyer')->willReturn(null);
        $conversationRepository
            ->expects(self::exactly(2))
            ->method('save')
            ->willReturnCallback(function (Conversation $conversation): void {
                $idReflection = new \ReflectionProperty($conversation, 'id');
                $idReflection->setAccessible(true);
                if (!$idReflection->isInitialized($conversation)) {
                    $idReflection->setValue($conversation, Id::fromInt(77));
                }
            });

        $messageRepository
            ->expects(self::once())
            ->method('save')
            ->willReturnCallback(function (Message $message): void {
                $idReflection = new \ReflectionProperty($message, 'id');
                $idReflection->setAccessible(true);
                if (!$idReflection->isInitialized($message)) {
                    $idReflection->setValue($message, Id::fromInt(88));
                }
            });

        $notificationBus
            ->expects(self::once())
            ->method('dispatch')
            ->willReturn(new Envelope(new \stdClass()));

        $handler = $this->createHandler(
            $conversationRepository,
            $messageRepository,
            $propertyRepository,
            $userRepository,
            $notificationBus,
        );

        $result = $handler(new SendMessageCommand(
            senderId: '10',
            propertyId: '100',
            text: 'Даты свободны',
            buyerId: '5',
        ));

        self::assertSame(77, $result['conversationId']);
    }

    public function testSellerCanLinkBookingInquiryWhenStartingConversation(): void
    {
        $conversationRepository = $this->createMock(ConversationRepositoryInterface::class);
        $messageRepository = $this->createMock(MessageRepositoryInterface::class);
        $propertyRepository = $this->createMock(PropertyRepositoryInterface::class);
        $userRepository = $this->createMock(UserRepositoryInterface::class);
        $bookingInquiryRepository = $this->createMock(BookingInquiryRepositoryInterface::class);
        $notificationBus = $this->createMock(MessageBusInterface::class);

        $property = $this->createMock(Property::class);
        $property->method('getOwnerId')->willReturn(Id::fromInt(10));
        $propertyRepository->method('findById')->willReturn($property);

        $buyer = User::register(Email::fromString('buyer@example.com'), '', 'Buyer', 'User');
        $userRepository->method('findById')->willReturn($buyer);

        $inquiry = new BookingInquiry(
            propertyId: Id::fromInt(100),
            ownerId: Id::fromInt(10),
            name: 'Иван',
            phone: '+375291112233',
            userId: Id::fromInt(5),
            email: 'buyer@example.com',
            checkIn: new \DateTimeImmutable('2026-08-05'),
            checkOut: new \DateTimeImmutable('2026-08-07'),
        );
        $this->setEntityId($inquiry, 15);

        $bookingInquiryRepository->method('findById')->with('15')->willReturn($inquiry);

        $savedConversation = null;
        $conversationRepository->method('findByPropertyAndBuyer')->willReturn(null);
        $conversationRepository
            ->expects(self::exactly(2))
            ->method('save')
            ->willReturnCallback(function (Conversation $conversation) use (&$savedConversation): void {
                $idReflection = new \ReflectionProperty($conversation, 'id');
                $idReflection->setAccessible(true);
                if (!$idReflection->isInitialized($conversation)) {
                    $idReflection->setValue($conversation, Id::fromInt(77));
                }
                $savedConversation = $conversation;
            });

        $messageRepository
            ->expects(self::once())
            ->method('save')
            ->willReturnCallback(function (Message $message): void {
                $idReflection = new \ReflectionProperty($message, 'id');
                $idReflection->setAccessible(true);
                if (!$idReflection->isInitialized($message)) {
                    $idReflection->setValue($message, Id::fromInt(88));
                }
            });

        $notificationBus
            ->expects(self::once())
            ->method('dispatch')
            ->willReturn(new Envelope(new \stdClass()));

        $handler = $this->createHandler(
            $conversationRepository,
            $messageRepository,
            $propertyRepository,
            $userRepository,
            $notificationBus,
            $bookingInquiryRepository,
        );

        $handler(new SendMessageCommand(
            senderId: '10',
            propertyId: '100',
            text: 'Подтверждаю бронирование',
            buyerId: '5',
            bookingInquiryId: '15',
        ));

        self::assertNotNull($savedConversation);
        self::assertSame('15', $savedConversation->getBookingInquiryId());
    }

    public function testSendFailsForInvalidBookingInquiry(): void
    {
        $conversationRepository = $this->createMock(ConversationRepositoryInterface::class);
        $propertyRepository = $this->createMock(PropertyRepositoryInterface::class);
        $userRepository = $this->createMock(UserRepositoryInterface::class);
        $bookingInquiryRepository = $this->createMock(BookingInquiryRepositoryInterface::class);

        $property = $this->createMock(Property::class);
        $property->method('getOwnerId')->willReturn(Id::fromInt(10));
        $propertyRepository->method('findById')->willReturn($property);

        $buyer = User::register(Email::fromString('buyer@example.com'), '', 'Buyer', 'User');
        $userRepository->method('findById')->willReturn($buyer);

        $inquiry = new BookingInquiry(
            propertyId: Id::fromInt(200),
            ownerId: Id::fromInt(10),
            name: 'Иван',
            phone: '+375291112233',
            userId: Id::fromInt(5),
        );
        $this->setEntityId($inquiry, 15);
        $bookingInquiryRepository->method('findById')->willReturn($inquiry);

        $conversationRepository->method('findByPropertyAndBuyer')->willReturn(null);

        $handler = $this->createHandler(
            $conversationRepository,
            $this->createStub(MessageRepositoryInterface::class),
            $propertyRepository,
            $userRepository,
            $this->createStub(MessageBusInterface::class),
            $bookingInquiryRepository,
        );

        $this->expectException(DomainException::class);
        $this->expectExceptionMessage('Заявка относится к другому объявлению');

        $handler(new SendMessageCommand(
            senderId: '10',
            propertyId: '100',
            text: 'Ответ',
            buyerId: '5',
            bookingInquiryId: '15',
        ));
    }

    public function testSendFailsWhenOwnerDisabledMessagesAndInquiries(): void
    {
        $conversationRepository = $this->createMock(ConversationRepositoryInterface::class);
        $propertyRepository = $this->createMock(PropertyRepositoryInterface::class);
        $userRepository = $this->createMock(UserRepositoryInterface::class);

        $property = $this->createMock(Property::class);
        $property->method('getOwnerId')->willReturn(Id::fromInt(10));

        $propertyRepository->method('findById')->willReturn($property);
        $conversationRepository->method('findByPropertyAndBuyer')->willReturn(null);

        $owner = User::register(Email::fromString('owner@example.com'), '', 'Owner', 'User');
        $owner->setAllowMessagesAndInquiries(false);
        $userRepository->method('findById')->willReturn($owner);

        $handler = $this->createHandler(
            $conversationRepository,
            $this->createStub(MessageRepositoryInterface::class),
            $propertyRepository,
            $userRepository,
            $this->createStub(MessageBusInterface::class),
        );

        $this->expectException(DomainException::class);
        $this->expectExceptionMessage('Владелец отключил приём сообщений и заявок на бронирование');

        $handler(new SendMessageCommand(
            senderId: '5',
            propertyId: '100',
            text: 'Привет!',
        ));
    }

    private function createHandler(
        ConversationRepositoryInterface $conversationRepository,
        MessageRepositoryInterface $messageRepository,
        PropertyRepositoryInterface $propertyRepository,
        UserRepositoryInterface $userRepository,
        MessageBusInterface $notificationBus,
        ?BookingInquiryRepositoryInterface $bookingInquiryRepository = null,
    ): SendMessageHandler {
        return new SendMessageHandler(
            $conversationRepository,
            $messageRepository,
            $propertyRepository,
            $userRepository,
            $bookingInquiryRepository ?? $this->createStub(BookingInquiryRepositoryInterface::class),
            $notificationBus,
        );
    }

    private function setEntityId(BookingInquiry $inquiry, int $id): void
    {
        $idReflection = new \ReflectionProperty($inquiry, 'id');
        $idReflection->setAccessible(true);
        $idReflection->setValue($inquiry, Id::fromInt($id));
    }
}
