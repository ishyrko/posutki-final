<?php

declare(strict_types=1);

namespace App\Infrastructure\Recaptcha;

use Symfony\Contracts\HttpClient\HttpClientInterface;

final class RecaptchaVerifier
{
    private const SITE_VERIFY_URL = 'https://www.google.com/recaptcha/api/siteverify';

    public function __construct(
        private readonly HttpClientInterface $httpClient,
        private readonly bool $enabled,
        private readonly string $secret,
    ) {
    }

    /**
     * Verifies a reCAPTCHA v2 checkbox token (same siteverify endpoint; response has no score/action).
     *
     * @throws RecaptchaException when verification fails or response is invalid
     */
    public function verify(string $token, ?string $remoteIp = null): void
    {
        if (!$this->enabled) {
            return;
        }

        if ($this->secret === '') {
            throw new RecaptchaException('reCAPTCHA не настроен на сервере');
        }

        $token = trim($token);
        if ($token === '') {
            throw new RecaptchaException('Не пройдена проверка reCAPTCHA');
        }

        $response = $this->httpClient->request('POST', self::SITE_VERIFY_URL, [
            'body' => [
                'secret' => $this->secret,
                'response' => $token,
                'remoteip' => $remoteIp ?? '',
            ],
        ]);

        $payload = json_decode($response->getContent(), true);
        if (!is_array($payload)) {
            throw new RecaptchaException('Проверка безопасности не пройдена');
        }

        if (!($payload['success'] ?? false)) {
            throw new RecaptchaException('Проверка безопасности не пройдена');
        }
    }
}
