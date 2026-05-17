<?php

declare(strict_types=1);

namespace App\Domain\Message\Event;

final readonly class MessageSentEvent
{
    public function __construct(
        public string $conversationId,
        public string $senderId,
        public string $messageText,
    ) {
    }
}
