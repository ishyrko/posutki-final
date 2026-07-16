<?php

declare(strict_types=1);

namespace App\Infrastructure\Payment\BePaid;

use Psr\Log\LoggerInterface;
use Symfony\Contracts\HttpClient\HttpClientInterface;

final readonly class BePaidGatewayClient
{
    public function __construct(
        private HttpClientInterface $httpClient,
        private LoggerInterface $logger,
        private string $shopId,
        private string $secretKey,
        private string $checkoutUrl,
        private bool $testMode,
    ) {
    }

    /**
     * @param array<string, mixed> $order
     * @param array<string, mixed> $settings
     * @param array<string, mixed>|null $customer
     *
     * @return array{token: string, redirectUrl: string}
     */
    public function createCheckout(array $order, array $settings, ?array $customer = null): array
    {
        $this->assertConfigured();

        $checkout = [
            'test' => $this->testMode,
            'transaction_type' => 'payment',
            'settings' => $settings,
            'order' => $order,
        ];

        if ($customer !== null && $customer !== []) {
            $checkout['customer'] = $customer;
        }

        $response = $this->httpClient->request(
            'POST',
            rtrim($this->checkoutUrl, '/') . '/ctp/api/checkouts',
            [
                'auth_basic' => [$this->shopId, $this->secretKey],
                'headers' => [
                    'Content-Type' => 'application/json',
                    'Accept' => 'application/json',
                    'X-API-Version' => '2',
                ],
                'json' => ['checkout' => $checkout],
            ],
        );

        $statusCode = $response->getStatusCode();
        $rawBody = $response->getContent(false);
        $body = $this->decodeJson($rawBody);

        if ($statusCode >= 400 || !is_array($body)) {
            $this->logger->error('bePaid createCheckout HTTP error', [
                'statusCode' => $statusCode,
                'response' => $body ?? $rawBody,
            ]);

            throw new \RuntimeException('Не удалось создать платёж в bePaid');
        }

        $token = $body['checkout']['token'] ?? null;
        $redirectUrl = $body['checkout']['redirect_url'] ?? null;

        if (!is_string($token) || $token === '' || !is_string($redirectUrl) || $redirectUrl === '') {
            $this->logger->error('bePaid createCheckout invalid response', ['response' => $body]);

            throw new \RuntimeException('Некорректный ответ bePaid при создании платежа');
        }

        return [
            'token' => $token,
            'redirectUrl' => $redirectUrl,
        ];
    }

    /**
     * @return array<string, mixed>
     */
    public function getCheckoutStatus(string $token): array
    {
        $this->assertConfigured();

        $response = $this->httpClient->request(
            'GET',
            rtrim($this->checkoutUrl, '/') . '/ctp/api/checkouts/' . rawurlencode($token),
            [
                'auth_basic' => [$this->shopId, $this->secretKey],
                'headers' => [
                    'Accept' => 'application/json',
                    'X-API-Version' => '2',
                ],
            ],
        );

        $statusCode = $response->getStatusCode();
        $rawBody = $response->getContent(false);
        $body = $this->decodeJson($rawBody);

        if ($statusCode >= 400 || !is_array($body)) {
            $this->logger->error('bePaid getCheckoutStatus HTTP error', [
                'statusCode' => $statusCode,
                'response' => $body ?? $rawBody,
            ]);

            throw new \RuntimeException('Не удалось получить статус платежа в bePaid');
        }

        return $body;
    }

    private function assertConfigured(): void
    {
        if ($this->shopId === '' || $this->secretKey === '') {
            throw new \RuntimeException('bePaid credentials are not configured');
        }
    }

    /**
     * @return array<string, mixed>|null
     */
    private function decodeJson(string $rawBody): ?array
    {
        if ($rawBody === '') {
            return null;
        }

        $decoded = json_decode($rawBody, true);

        return is_array($decoded) ? $decoded : null;
    }
}
