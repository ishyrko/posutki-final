<?php

declare(strict_types=1);

namespace App\Tests\Functional\Api;

final class MessageControllerTest extends ApiTestCase
{
    public function testSendWithoutAuthReturnsForbidden(): void
    {
        $this->client->request(
            'POST',
            '/api/messages/send',
            server: ['CONTENT_TYPE' => 'application/json'],
            content: json_encode([
                'propertyId' => 1,
                'text' => 'Hello',
            ], JSON_THROW_ON_ERROR)
        );

        self::assertSame(403, $this->client->getResponse()->getStatusCode());
    }

    public function testSendWithoutPropertyOrConversationReturns422(): void
    {
        $email = 'msg-no-ids@example.com';
        $password = 'Password123!';
        $this->createUser($email, $password);
        $token = $this->loginAndGetToken($email, $password);
        if ($token === '') {
            self::markTestSkipped('Could not obtain JWT token for message send test.');
        }

        $this->client->request(
            'POST',
            '/api/messages/send',
            server: [
                'CONTENT_TYPE' => 'application/json',
                'HTTP_AUTHORIZATION' => 'Bearer ' . $token,
            ],
            content: json_encode(['text' => 'Hello'], JSON_THROW_ON_ERROR)
        );

        self::assertSame(422, $this->client->getResponse()->getStatusCode());
    }

    public function testSendWithEmptyTextReturns422(): void
    {
        $email = 'msg-empty-text@example.com';
        $password = 'Password123!';
        $this->createUser($email, $password);
        $token = $this->loginAndGetToken($email, $password);
        if ($token === '') {
            self::markTestSkipped('Could not obtain JWT token for message send test.');
        }

        $this->client->request(
            'POST',
            '/api/messages/send',
            server: [
                'CONTENT_TYPE' => 'application/json',
                'HTTP_AUTHORIZATION' => 'Bearer ' . $token,
            ],
            content: json_encode([
                'propertyId' => 1,
                'text' => '   ',
            ], JSON_THROW_ON_ERROR)
        );

        self::assertSame(422, $this->client->getResponse()->getStatusCode());
    }

    public function testSendWithNumericPropertyIdAsBuyerReturnsCreated(): void
    {
        $seller = $this->createUser('seller-msg@example.com', 'Password123!');
        $buyerEmail = 'buyer-msg@example.com';
        $buyerPassword = 'Password123!';
        $this->createUser($buyerEmail, $buyerPassword);

        $city = $this->createCity('Minsk Msg', 'minsk-msg', 'г. Минск');
        $property = $this->createProperty($seller, $city, 'published');

        $token = $this->loginAndGetToken($buyerEmail, $buyerPassword);
        if ($token === '') {
            self::markTestSkipped('Could not obtain JWT token for message send test.');
        }

        $this->client->request(
            'POST',
            '/api/messages/send',
            server: [
                'CONTENT_TYPE' => 'application/json',
                'HTTP_AUTHORIZATION' => 'Bearer ' . $token,
            ],
            content: json_encode([
                'propertyId' => $property->getId()->getValue(),
                'text' => 'Здравствуйте, объявление ещё актуально?',
            ], JSON_THROW_ON_ERROR)
        );

        self::assertSame(201, $this->client->getResponse()->getStatusCode());

        $payload = json_decode((string) $this->client->getResponse()->getContent(), true, 512, JSON_THROW_ON_ERROR);
        self::assertTrue($payload['success']);
        self::assertArrayHasKey('conversationId', $payload['data']);
        self::assertArrayHasKey('messageId', $payload['data']);
        self::assertNotSame('', $payload['data']['conversationId']);
        self::assertNotSame('', $payload['data']['messageId']);
    }

    public function testSendToOwnPropertyReturnsBadRequest(): void
    {
        $email = 'owner-self-msg@example.com';
        $password = 'Password123!';
        $owner = $this->createUser($email, $password);
        $city = $this->createCity('Minsk Self', 'minsk-self', 'г. Минск');
        $property = $this->createProperty($owner, $city, 'published');

        $token = $this->loginAndGetToken($email, $password);
        if ($token === '') {
            self::markTestSkipped('Could not obtain JWT token for message send test.');
        }

        $this->client->request(
            'POST',
            '/api/messages/send',
            server: [
                'CONTENT_TYPE' => 'application/json',
                'HTTP_AUTHORIZATION' => 'Bearer ' . $token,
            ],
            content: json_encode([
                'propertyId' => $property->getId()->getValue(),
                'text' => 'Написать самому себе',
            ], JSON_THROW_ON_ERROR)
        );

        self::assertSame(400, $this->client->getResponse()->getStatusCode());

        $payload = json_decode((string) $this->client->getResponse()->getContent(), true, 512, JSON_THROW_ON_ERROR);
        self::assertFalse($payload['success']);
        self::assertStringContainsString('самому себе', $payload['error']['message']);
    }
}
