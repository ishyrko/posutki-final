<?php

declare(strict_types=1);

namespace App\Application\Notification\Message;

use App\Domain\Message\Event\MessageSentEvent;
use App\Domain\Message\Repository\ConversationRepositoryInterface;
use App\Domain\Property\Repository\PropertyRepositoryInterface;
use App\Domain\Shared\ValueObject\Id;
use App\Domain\User\Repository\UserRepositoryInterface;
use App\Infrastructure\Mail\MessageMailer;

final readonly class MessageSentHandler
{
    public function __construct(
        private ConversationRepositoryInterface $conversationRepository,
        private PropertyRepositoryInterface $propertyRepository,
        private UserRepositoryInterface $userRepository,
        private MessageMailer $mailer,
    ) {
    }

    public function __invoke(MessageSentEvent $event): void
    {
        $conversation = $this->conversationRepository->findById(
            Id::fromString($event->conversationId)
        );
        if ($conversation === null) {
            return;
        }

        $recipientId = $event->senderId === $conversation->getSellerId()
            ? $conversation->getBuyerId()
            : $conversation->getSellerId();

        $recipient = $this->userRepository->findById(Id::fromString($recipientId));
        $sender = $this->userRepository->findById(Id::fromString($event->senderId));
        if ($recipient === null || $sender === null) {
            return;
        }

        $property = $this->propertyRepository->findById(
            Id::fromString($conversation->getPropertyId())
        );
        if ($property === null) {
            return;
        }

        $this->mailer->sendNewMessage(
            recipient: $recipient,
            sender: $sender,
            property: $property,
            messageText: $event->messageText,
        );
    }
}
