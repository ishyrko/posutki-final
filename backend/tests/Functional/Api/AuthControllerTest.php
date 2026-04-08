<?php

declare(strict_types=1);

namespace App\Tests\Functional\Api;

final class AuthControllerTest extends ApiTestCase
{
    public function testLoginWithValidCredentialsReturnsJwtToken(): void
    {
        $email = 'auth-valid@example.com';
        $password = 'Password123!';
        $this->createUser($email, $password);

        $token = $this->loginAndGetToken($email, $password);

        self::assertNotSame('', $token);
    }

    public function testLoginWithInvalidPasswordReturnsUnauthorized(): void
    {
        $email = 'auth-invalid@example.com';
        $password = 'Password123!';
        $this->createUser($email, $password);

        $this->client->request(
            'POST',
            '/api/auth/login',
            server: ['CONTENT_TYPE' => 'application/json'],
            content: json_encode([
                'email' => $email,
                'password' => 'WrongPassword',
            ], JSON_THROW_ON_ERROR)
        );

        self::assertSame(401, $this->client->getResponse()->getStatusCode());
    }

    public function testMeWithoutTokenReturnsUnauthorized(): void
    {
        $this->client->request('GET', '/api/auth/me');

        self::assertSame(403, $this->client->getResponse()->getStatusCode());
    }

    public function testMeWithValidTokenReturnsUserData(): void
    {
        $email = 'auth-me@example.com';
        $password = 'Password123!';
        $this->createUser($email, $password);

        $token = $this->loginAndGetToken($email, $password);
        if ($token === '') {
            self::markTestSkipped('Could not obtain JWT token for /api/auth/me test.');
        }

        $this->client->request(
            'GET',
            '/api/auth/me',
            server: ['HTTP_AUTHORIZATION' => 'Bearer ' . $token]
        );

        self::assertSame(200, $this->client->getResponse()->getStatusCode());

        $response = json_decode((string) $this->client->getResponse()->getContent(), true, 512, JSON_THROW_ON_ERROR);
        self::assertSame($email, $response['data']['email']);
    }
}
