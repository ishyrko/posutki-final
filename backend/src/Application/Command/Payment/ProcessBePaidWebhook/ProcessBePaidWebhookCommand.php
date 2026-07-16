<?php

declare(strict_types=1);

namespace App\Application\Command\Payment\ProcessBePaidWebhook;

final class ProcessBePaidWebhookCommand
{
    /**
     * @param array<string, mixed> $payload
     */
    public function __construct(
        public readonly array $payload,
        public readonly string $rawBody,
        public readonly bool $signatureValid,
    ) {
    }
}
