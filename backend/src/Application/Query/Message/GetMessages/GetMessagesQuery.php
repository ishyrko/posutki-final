<?php

declare(strict_types=1);

namespace App\Application\Query\Message\GetMessages;

final class GetMessagesQuery
{
    public function __construct(
        public readonly string $conversationId,
        public readonly string $userId,
        public readonly int $page = 1,
        public readonly int $limit = 50,
    ) {
    }
}
