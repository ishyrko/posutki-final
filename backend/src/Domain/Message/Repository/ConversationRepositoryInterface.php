<?php

declare(strict_types=1);

namespace App\Domain\Message\Repository;

use App\Domain\Message\Entity\Conversation;
use App\Domain\Shared\ValueObject\Id;

interface ConversationRepositoryInterface
{
    public function save(Conversation $conversation): void;

    public function findById(Id $id): ?Conversation;

    public function findByPropertyAndBuyer(string $propertyId, string $buyerId): ?Conversation;

    /** @return Conversation[] */
    public function findByUser(string $userId, int $page = 1, int $limit = 20): array;

    public function countByUser(string $userId): int;

    public function countUnreadByUser(string $userId): int;
}
