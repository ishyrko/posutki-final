<?php

declare(strict_types=1);

namespace App\Infrastructure\Sms;

use App\Domain\User\Service\SmsServiceInterface;
use Psr\Log\LoggerInterface;
use Symfony\Contracts\HttpClient\HttpClientInterface;

final readonly class RocketSmsService implements SmsServiceInterface
{
    private const SEND_URL = 'https://api.rocketsms.by/simple/send';

    public function __construct(
        private HttpClientInterface $httpClient,
        private LoggerInterface $logger,
        private string $username,
        private string $password,
        private ?string $sender,
        private string $verificationTemplate,
    ) {
    }

    public function send(string $phone, string $code): void
    {
        if ($this->username === '' || $this->password === '') {
            throw new \RuntimeException('RocketSMS credentials are not configured');
        }

        $normalizedPhone = $this->normalizePhone($phone);
        $message = sprintf($this->verificationTemplate, $code);

        $payload = [
            'username' => $this->username,
            'password' => $this->password,
            'phone' => $normalizedPhone,
            'text' => $message,
            'priority' => true,
        ];

        if ($this->sender !== null && $this->sender !== '') {
            $payload['sender'] = $this->sender;
        }

        $response = $this->httpClient->request('POST', self::SEND_URL, [
            'query' => $payload,
        ]);

        $statusCode = $response->getStatusCode();
        $rawBody = $response->getContent(false);
        $body = $this->decodeJson($rawBody);

        if ($statusCode >= 400) {
            $this->logger->error('RocketSMS returned HTTP error', [
                'statusCode' => $statusCode,
                'response' => $body ?? $rawBody,
            ]);

            throw new \RuntimeException('Failed to send SMS via RocketSMS');
        }

        if (is_array($body) && !empty($body['error'])) {
            $this->logger->error('RocketSMS returned API error', [
                'response' => $body,
            ]);

            throw new \RuntimeException('RocketSMS rejected SMS request');
        }

        $this->logger->info('SMS verification code sent via RocketSMS', [
            'phone' => $this->maskPhone($normalizedPhone),
        ]);
    }

    private function decodeJson(string $rawBody): ?array
    {
        if ($rawBody === '') {
            return null;
        }

        try {
            $decoded = json_decode($rawBody, true, 512, JSON_THROW_ON_ERROR);

            return is_array($decoded) ? $decoded : null;
        } catch (\JsonException) {
            return null;
        }
    }

    private function normalizePhone(string $phone): string
    {
        $normalized = preg_replace('/\D+/', '', $phone) ?? '';

        if ($normalized === '') {
            throw new \InvalidArgumentException('Phone number is empty after normalization');
        }

        return $normalized;
    }

    private function maskPhone(string $phone): string
    {
        if (strlen($phone) <= 4) {
            return '****';
        }

        return str_repeat('*', strlen($phone) - 4) . substr($phone, -4);
    }
}
