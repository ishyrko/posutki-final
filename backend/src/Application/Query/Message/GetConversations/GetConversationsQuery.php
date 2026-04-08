<?php

declare(strict_types=1);

namespace App\Application\Query\Message\GetConversations;

final class GetConversationsQuery
{
    public function __construct(
        public readonly string $userId,
        public readonly int $page = 1,
        public readonly int $limit = 20,
    ) {
    }
}
