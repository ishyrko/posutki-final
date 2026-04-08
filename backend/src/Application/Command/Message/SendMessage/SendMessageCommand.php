<?php

declare(strict_types=1);

namespace App\Application\Command\Message\SendMessage;

final class SendMessageCommand
{
    public function __construct(
        public readonly string $senderId,
        public readonly string $propertyId,
        public readonly string $text,
        public readonly ?string $conversationId = null,
    ) {
    }
}
