<?php

declare(strict_types=1);

namespace App\Domain\Message\Repository;

use App\Domain\Message\Entity\Message;

interface MessageRepositoryInterface
{
    public function save(Message $message): void;

    /** @return Message[] */
    public function findByConversation(string $conversationId, int $page = 1, int $limit = 50): array;

    public function countByConversation(string $conversationId): int;

    public function markAllReadInConversation(string $conversationId, string $readByUserId): void;
}
