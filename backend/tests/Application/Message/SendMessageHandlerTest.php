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
use App\Domain\Shared\ValueObject\Id;
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
        $notificationBus = $this->createMock(MessageBusInterface::class);

        $property = $this->createMock(Property::class);
        $property->method('getOwnerId')->willReturn(Id::fromInt(10));

        $propertyRepository
            ->method('findById')
            ->willReturn($property);

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
            $notificationBus,
        );

        $handler(new SendMessageCommand(
            senderId: '5',
            propertyId: '100',
            text: 'Привет!',
        ));
    }
}
