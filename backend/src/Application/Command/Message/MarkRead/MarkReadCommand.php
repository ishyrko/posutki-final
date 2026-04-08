<?php

declare(strict_types=1);

namespace App\Application\Command\Message\MarkRead;

final class MarkReadCommand
{
    public function __construct(
        public readonly string $conversationId,
        public readonly string $userId,
    ) {
    }
}
