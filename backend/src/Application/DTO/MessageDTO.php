<?php

declare(strict_types=1);

namespace App\Application\DTO;

use App\Domain\Message\Entity\Message;

final class MessageDTO implements \JsonSerializable
{
    public function __construct(
        public readonly int $id,
        public readonly int $conversationId,
        public readonly int $senderId,
        public readonly string $text,
        public readonly bool $isRead,
        public readonly \DateTimeImmutable $createdAt,
    ) {
    }

    public static function fromEntity(Message $message): self
    {
        return new self(
            id: $message->getId()->getValue(),
            conversationId: (int) $message->getConversationId(),
            senderId: (int) $message->getSenderId(),
            text: $message->getText(),
            isRead: $message->isRead(),
            createdAt: $message->getCreatedAt(),
        );
    }

    public function jsonSerialize(): array
    {
        return [
            'id' => $this->id,
            'conversationId' => $this->conversationId,
            'senderId' => $this->senderId,
            'text' => $this->text,
            'isRead' => $this->isRead,
            'createdAt' => $this->createdAt->format('c'),
        ];
    }
}
