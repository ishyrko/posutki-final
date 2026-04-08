<?php

declare(strict_types=1);

namespace App\Domain\Message\Entity;

use App\Domain\Shared\ValueObject\Id;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity]
#[ORM\Table(name: 'messages')]
#[ORM\Index(columns: ['conversation_id', 'created_at'])]
class Message
{
    #[ORM\Id]
    #[ORM\Column(type: 'id')]
    #[ORM\GeneratedValue]
    private Id $id;

    #[ORM\Column(type: 'id', name: 'conversation_id')]
    private Id $conversationId;

    #[ORM\Column(type: 'id', name: 'sender_id')]
    private Id $senderId;

    #[ORM\Column(type: 'text')]
    private string $text;

    #[ORM\Column(type: 'boolean', name: 'is_read')]
    private bool $isRead = false;

    #[ORM\Column(type: 'datetime_immutable', name: 'created_at')]
    private \DateTimeImmutable $createdAt;

    public function __construct(
        Id $conversationId,
        Id $senderId,
        string $text,
    ) {
        $this->conversationId = $conversationId;
        $this->senderId = $senderId;
        $this->text = $text;
        $this->createdAt = new \DateTimeImmutable();
    }

    public function getId(): Id
    {
        return $this->id;
    }

    public function getConversationId(): string
    {
        return (string) $this->conversationId->getValue();
    }

    public function getSenderId(): string
    {
        return (string) $this->senderId->getValue();
    }

    public function getText(): string
    {
        return $this->text;
    }

    public function isRead(): bool
    {
        return $this->isRead;
    }

    public function getCreatedAt(): \DateTimeImmutable
    {
        return $this->createdAt;
    }

    public function markRead(): void
    {
        $this->isRead = true;
    }
}
