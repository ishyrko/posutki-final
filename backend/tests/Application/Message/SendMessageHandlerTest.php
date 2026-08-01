<?php

declare(strict_types=1);

namespace App\Tests\Application\Message;

use App\Application\Command\Message\SendMessage\SendMessageCommand;
use App\Application\Command\Message\SendMessage\SendMessageHandler;
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

        $handler = new SendMessageHandler(
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

        $handler = new SendMessageHandler(
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
}
