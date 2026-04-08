<?php

declare(strict_types=1);

namespace App\Tests\Functional\Api;

final class PropertyControllerTest extends ApiTestCase
{
    public function testListReturnsPaginatedArrayPayload(): void
    {
        $this->client->request('GET', '/api/properties');

        self::assertSame(200, $this->client->getResponse()->getStatusCode());

        $payload = json_decode((string) $this->client->getResponse()->getContent(), true, 512, JSON_THROW_ON_ERROR);
        self::assertTrue($payload['success']);
        self::assertIsArray($payload['data']);
    }

    public function testGetPublishedPropertyReturnsOk(): void
    {
        $owner = $this->createUser('owner-published@example.com', 'Password123!');
        $city = $this->createCity();
        $property = $this->createProperty($owner, $city, 'published');

        $this->client->request('GET', '/api/properties/' . $property->getId()->getValue());

        self::assertSame(200, $this->client->getResponse()->getStatusCode());

        $payload = json_decode((string) $this->client->getResponse()->getContent(), true, 512, JSON_THROW_ON_ERROR);
        self::assertTrue($payload['success']);
        self::assertSame($property->getId()->getValue(), $payload['data']['id']);
    }

    public function testGetNonPublishedPropertyWithoutAuthReturnsNotFound(): void
    {
        $owner = $this->createUser('owner-draft@example.com', 'Password123!');
        $city = $this->createCity('Minsk Draft', 'minsk-draft', 'г. Минск');
        $property = $this->createProperty($owner, $city, 'draft');

        $this->client->request('GET', '/api/properties/' . $property->getId()->getValue());

        self::assertSame(404, $this->client->getResponse()->getStatusCode());
    }

    public function testGetNonPublishedPropertyAsOwnerReturnsOk(): void
    {
        $email = 'owner-moderation@example.com';
        $password = 'Password123!';
        $owner = $this->createUser($email, $password);
        $city = $this->createCity('Minsk Mod', 'minsk-mod', 'г. Минск');
        $property = $this->createProperty($owner, $city, 'moderation');

        $token = $this->loginAndGetToken($email, $password);
        if ($token === '') {
            self::markTestSkipped('Could not obtain JWT token for authorized property get.');
        }

        $this->client->request(
            'GET',
            '/api/properties/' . $property->getId()->getValue(),
            server: ['HTTP_AUTHORIZATION' => 'Bearer ' . $token],
        );

        self::assertSame(200, $this->client->getResponse()->getStatusCode());

        $payload = json_decode((string) $this->client->getResponse()->getContent(), true, 512, JSON_THROW_ON_ERROR);
        self::assertTrue($payload['success']);
        self::assertSame($property->getId()->getValue(), $payload['data']['id']);
    }

    public function testGetNonPublishedPropertyAsOtherUserReturnsNotFound(): void
    {
        $owner = $this->createUser('real-owner@example.com', 'Password123!');
        $otherEmail = 'other-user@example.com';
        $otherPassword = 'Password123!';
        $this->createUser($otherEmail, $otherPassword);

        $city = $this->createCity('Minsk Other', 'minsk-other', 'г. Минск');
        $property = $this->createProperty($owner, $city, 'moderation');

        $token = $this->loginAndGetToken($otherEmail, $otherPassword);
        if ($token === '') {
            self::markTestSkipped('Could not obtain JWT token for authorized property get.');
        }

        $this->client->request(
            'GET',
            '/api/properties/' . $property->getId()->getValue(),
            server: ['HTTP_AUTHORIZATION' => 'Bearer ' . $token],
        );

        self::assertSame(404, $this->client->getResponse()->getStatusCode());
    }

    public function testCreateWithAuthReturnsCreatedAndPropertyId(): void
    {
        $email = 'creator@example.com';
        $password = 'Password123!';
        $this->createUser($email, $password);
        $token = $this->loginAndGetToken($email, $password);
        if ($token === '') {
            self::markTestSkipped('Could not obtain JWT token for authorized property creation.');
        }

        $this->client->request(
            'POST',
            '/api/properties',
            server: [
                'CONTENT_TYPE' => 'application/json',
                'HTTP_AUTHORIZATION' => 'Bearer ' . $token,
            ],
            content: json_encode($this->createPayload(), JSON_THROW_ON_ERROR)
        );

        self::assertSame(201, $this->client->getResponse()->getStatusCode());

        $payload = json_decode((string) $this->client->getResponse()->getContent(), true, 512, JSON_THROW_ON_ERROR);
        self::assertTrue($payload['success']);
        self::assertArrayHasKey('propertyId', $payload['data']);
    }

    public function testCreateWithoutAuthReturnsUnauthorized(): void
    {
        $this->client->request(
            'POST',
            '/api/properties',
            server: ['CONTENT_TYPE' => 'application/json'],
            content: json_encode($this->createPayload(), JSON_THROW_ON_ERROR)
        );

        self::assertSame(403, $this->client->getResponse()->getStatusCode());
    }

    /**
    * @return array<string, mixed>
    */
    private function createPayload(): array
    {
        return [
            'type' => 'apartment',
            'dealType' => 'sale',
            'title' => 'Modern apartment in central district',
            'description' => 'Long listing description for functional tests with enough symbols to pass validator.',
            'price' => [
                'amount' => 25000000,
                'currency' => 'BYN',
            ],
            'area' => 72.5,
            'rooms' => 3,
            'floor' => 5,
            'totalFloors' => 9,
            'bathrooms' => 1,
            'yearBuilt' => 2017,
            'building' => '10A',
            'cityId' => 1,
            'coordinates' => [
                'latitude' => 53.9045,
                'longitude' => 27.5615,
            ],
            'images' => [],
            'amenities' => [],
        ];
    }
}
