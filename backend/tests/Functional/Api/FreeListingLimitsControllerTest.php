<?php

declare(strict_types=1);

namespace App\Tests\Functional\Api;

use App\Domain\Property\Entity\Property;

final class FreeListingLimitsControllerTest extends ApiTestCase
{
    public function testUnarchiveReturnsAwaitingPaymentWhenAccountLimitReached(): void
    {
        $email = 'owner-free-limit@example.com';
        $password = 'Password123!';
        $owner = $this->createUser($email, $password);
        $city = $this->createCity('Minsk Free Limit', 'minsk-free-limit', 'г. Минск');

        for ($i = 0; $i < 5; ++$i) {
            $this->createProperty($owner, $city, 'published');
        }

        $sixth = $this->createProperty($owner, $city, 'published');
        $sixth->archive();
        $this->entityManager()->flush();
        $sixthId = $sixth->getId()->getValue();

        $token = $this->loginAndGetToken($email, $password);
        if ($token === '') {
            self::markTestSkipped('Could not obtain JWT token.');
        }

        $auth = ['HTTP_AUTHORIZATION' => 'Bearer ' . $token];
        $this->client->request('POST', '/api/properties/' . $sixthId . '/unarchive', server: $auth);
        self::assertSame(200, $this->client->getResponse()->getStatusCode());

        $payload = json_decode((string) $this->client->getResponse()->getContent(), true, 512, JSON_THROW_ON_ERROR);
        self::assertTrue($payload['data']['requiresPayment']);

        $this->entityManager()->clear();
        $property = $this->entityManager()->find(Property::class, $sixthId);
        self::assertNotNull($property);
        self::assertSame('awaiting_payment', $property->getStatus());
    }

    public function testFreeLimitEndpointReturnsAccountUsage(): void
    {
        $email = 'owner-free-limit-api@example.com';
        $password = 'Password123!';
        $owner = $this->createUser($email, $password);
        $city = $this->createCity('Minsk Free Limit API', 'minsk-free-limit-api', 'г. Минск');
        $this->createProperty($owner, $city, 'published');
        $this->createProperty($owner, $city, 'published');

        $token = $this->loginAndGetToken($email, $password);
        if ($token === '') {
            self::markTestSkipped('Could not obtain JWT token.');
        }

        $this->client->request(
            'GET',
            '/api/properties/free-limit?cityId=' . $city->getId() . '&type=apartment',
            server: ['HTTP_AUTHORIZATION' => 'Bearer ' . $token],
        );

        self::assertSame(200, $this->client->getResponse()->getStatusCode());
        $payload = json_decode((string) $this->client->getResponse()->getContent(), true, 512, JSON_THROW_ON_ERROR);
        self::assertSame(2, $payload['data']['account']['used']);
        self::assertSame(5, $payload['data']['account']['limit']);
        self::assertIsArray($payload['data']['city']);
    }
}
