<?php

declare(strict_types=1);

namespace App\Infrastructure\Payment\BePaid;

use Psr\Log\LoggerInterface;

final readonly class BePaidSignatureVerifier
{
    public function __construct(
        private LoggerInterface $logger,
        private string $publicKey,
    ) {
    }

    public function verify(string $rawBody, ?string $signatureHeader): bool
    {
        if ($this->publicKey === '') {
            $this->logger->warning('bePaid webhook signature verification skipped: BEPAID_PUBLIC_KEY is not configured');

            return true;
        }

        if ($signatureHeader === null || $signatureHeader === '') {
            $this->logger->warning('bePaid webhook missing Content-Signature header');

            return false;
        }

        $publicKeyPem = $this->toPem($this->publicKey);
        $key = openssl_pkey_get_public($publicKeyPem);
        if ($key === false) {
            $this->logger->error('bePaid webhook public key is invalid');

            return false;
        }

        $signature = base64_decode($signatureHeader, true);
        if ($signature === false) {
            $this->logger->warning('bePaid webhook Content-Signature is not valid base64');

            return false;
        }

        $result = openssl_verify($rawBody, $signature, $key, OPENSSL_ALGO_SHA256);

        return $result === 1;
    }

    private function toPem(string $publicKey): string
    {
        $normalized = str_replace(["\r\n", "\n"], '', trim($publicKey));
        $normalized = chunk_split($normalized, 64, "\n");

        return "-----BEGIN PUBLIC KEY-----\n{$normalized}-----END PUBLIC KEY-----";
    }
}
