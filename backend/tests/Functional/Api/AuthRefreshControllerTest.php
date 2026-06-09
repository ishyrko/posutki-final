<?php

declare(strict_types=1);

namespace App\Tests\Functional\Api;

final class AuthRefreshControllerTest extends ApiTestCase
{
    public function testLoginReturnsRefreshToken(): void
    {
        $email = 'refresh-login@example.com';
        $password = 'Password123!';
        $this->createUser($email, $password);

        $this->client->request(
            'POST',
            '/api/auth/login',
            server: ['CONTENT_TYPE' => 'application/json'],
            content: json_encode([
                'email' => $email,
                'password' => $password,
            ], JSON_THROW_ON_ERROR),
        );

        self::assertSame(200, $this->client->getResponse()->getStatusCode());

        $response = json_decode((string) $this->client->getResponse()->getContent(), true, 512, JSON_THROW_ON_ERROR);
        self::assertNotSame('', $response['token'] ?? '');
        self::assertNotSame('', $response['refreshToken'] ?? '');
    }

    public function testRefreshReturnsNewTokenPair(): void
    {
        $email = 'refresh-exchange@example.com';
        $password = 'Password123!';
        $this->createUser($email, $password);

        $this->client->request(
            'POST',
            '/api/auth/login',
            server: ['CONTENT_TYPE' => 'application/json'],
            content: json_encode([
                'email' => $email,
                'password' => $password,
            ], JSON_THROW_ON_ERROR),
        );

        $login = json_decode((string) $this->client->getResponse()->getContent(), true, 512, JSON_THROW_ON_ERROR);
        $refreshToken = $login['refreshToken'] ?? '';
        self::assertNotSame('', $refreshToken);

        $this->client->request(
            'POST',
            '/api/auth/refresh',
            server: ['CONTENT_TYPE' => 'application/json'],
            content: json_encode(['refreshToken' => $refreshToken], JSON_THROW_ON_ERROR),
        );

        self::assertSame(200, $this->client->getResponse()->getStatusCode());

        $response = json_decode((string) $this->client->getResponse()->getContent(), true, 512, JSON_THROW_ON_ERROR);
        self::assertNotSame('', $response['data']['token'] ?? '');
        self::assertNotSame('', $response['data']['refreshToken'] ?? '');
        self::assertNotSame($refreshToken, $response['data']['refreshToken']);
    }

    public function testRefreshWithUsedTokenFails(): void
    {
        $email = 'refresh-once@example.com';
        $password = 'Password123!';
        $this->createUser($email, $password);

        $this->client->request(
            'POST',
            '/api/auth/login',
            server: ['CONTENT_TYPE' => 'application/json'],
            content: json_encode([
                'email' => $email,
                'password' => $password,
            ], JSON_THROW_ON_ERROR),
        );

        $login = json_decode((string) $this->client->getResponse()->getContent(), true, 512, JSON_THROW_ON_ERROR);
        $refreshToken = $login['refreshToken'] ?? '';

        $this->client->request(
            'POST',
            '/api/auth/refresh',
            server: ['CONTENT_TYPE' => 'application/json'],
            content: json_encode(['refreshToken' => $refreshToken], JSON_THROW_ON_ERROR),
        );
        self::assertSame(200, $this->client->getResponse()->getStatusCode());

        $this->client->request(
            'POST',
            '/api/auth/refresh',
            server: ['CONTENT_TYPE' => 'application/json'],
            content: json_encode(['refreshToken' => $refreshToken], JSON_THROW_ON_ERROR),
        );
        self::assertSame(401, $this->client->getResponse()->getStatusCode());
    }
}
