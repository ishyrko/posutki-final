<?php

declare(strict_types=1);

namespace App\Application\Command\ContactFeedback\Submit;

final class SubmitFeedbackCommand
{
    public function __construct(
        public readonly string $name,
        public readonly string $email,
        public readonly string $subject,
        public readonly string $message,
    ) {
    }
}
