<?php

declare(strict_types=1);

namespace App\Tests\Functional\Api;

use App\Domain\Property\Entity\Property;

final class PropertyLifecycleControllerTest extends ApiTestCase
{
    public function testGetArchivedPropertyReturnsNotFoundForGuest(): void
    {
        $email = 'owner-archived-get@example.com';
        $password = 'Password123!';
        $owner = $this->createUser($email, $password);
        $city = $this->createCity('Minsk Archived Get', 'minsk-archived-get', 'г. Минск');
        $property = $this->createProperty($owner, $city, 'published');
        $property->archive();
        $this->entityManager()->flush();

        $this->client->request(
            'GET',
            '/api/properties/' . $property->getId()->getValue(),
        );

        self::assertSame(404, $this->client->getResponse()->getStatusCode());
    }

    public function testGetArchivedPropertyReturnsOkForOwner(): void
    {
        $email = 'owner-archived-get-owner@example.com';
        $password = 'Password123!';
        $owner = $this->createUser($email, $password);
        $city = $this->createCity('Minsk Archived Get Owner', 'minsk-archived-get-owner', 'г. Минск');
        $property = $this->createProperty($owner, $city, 'published');
        $property->archive();
        $this->entityManager()->flush();

        $token = $this->loginAndGetToken($email, $password);
        if ($token === '') {
            self::markTestSkipped('Could not obtain JWT token.');
        }

        $this->client->request(
            'GET',
            '/api/properties/' . $property->getId()->getValue(),
            server: ['HTTP_AUTHORIZATION' => 'Bearer ' . $token],
        );

        self::assertSame(200, $this->client->getResponse()->getStatusCode());
        $payload = json_decode((string) $this->client->getResponse()->getContent(), true, 512, JSON_THROW_ON_ERROR);
        self::assertSame('archived', $payload['data']['status']);
    }

    public function testGetDeletedPropertyReturnsOkForOwner(): void
    {
        $email = 'owner-deleted-get-owner@example.com';
        $password = 'Password123!';
        $owner = $this->createUser($email, $password);
        $city = $this->createCity('Minsk Deleted Get Owner', 'minsk-deleted-get-owner', 'г. Минск');
        $property = $this->createProperty($owner, $city, 'published');
        $property->delete();
        $this->entityManager()->flush();

        $token = $this->loginAndGetToken($email, $password);
        if ($token === '') {
            self::markTestSkipped('Could not obtain JWT token.');
        }

        $this->client->request(
            'GET',
            '/api/properties/' . $property->getId()->getValue(),
            server: ['HTTP_AUTHORIZATION' => 'Bearer ' . $token],
        );

        self::assertSame(200, $this->client->getResponse()->getStatusCode());
        $payload = json_decode((string) $this->client->getResponse()->getContent(), true, 512, JSON_THROW_ON_ERROR);
        self::assertSame('deleted', $payload['data']['status']);
    }

    public function testArchiveAndUnarchivePublishedPropertyAsOwner(): void
    {
        $email = 'owner-archive-cycle@example.com';
        $password = 'Password123!';
        $owner = $this->createUser($email, $password);
        $city = $this->createCity('Minsk Archive Cycle', 'minsk-archive-cycle', 'г. Минск');
        $property = $this->createProperty($owner, $city, 'published');
        $propertyId = $property->getId()->getValue();

        $token = $this->loginAndGetToken($email, $password);
        if ($token === '') {
            self::markTestSkipped('Could not obtain JWT token.');
        }

        $auth = ['HTTP_AUTHORIZATION' => 'Bearer ' . $token];

        $this->client->request('POST', '/api/properties/' . $propertyId . '/archive', server: $auth);
        self::assertSame(200, $this->client->getResponse()->getStatusCode());
        $archivePayload = json_decode((string) $this->client->getResponse()->getContent(), true, 512, JSON_THROW_ON_ERROR);
        self::assertTrue($archivePayload['success']);
        self::assertNotEmpty($archivePayload['data']['archivedAt']);

        $this->entityManager()->clear();
        $archived = $this->entityManager()->find(Property::class, $propertyId);
        self::assertNotNull($archived);
        self::assertSame('archived', $archived->getStatus());

        $this->client->request('POST', '/api/properties/' . $propertyId . '/unarchive', server: $auth);
        self::assertSame(200, $this->client->getResponse()->getStatusCode());

        $this->entityManager()->clear();
        $reactivated = $this->entityManager()->find(Property::class, $propertyId);
        self::assertNotNull($reactivated);
        self::assertSame('published', $reactivated->getStatus());
        self::assertNull($reactivated->getArchivedAt());
    }

    public function testDeleteDraftPropertyAsOwner(): void
    {
        $email = 'owner-delete-draft@example.com';
        $password = 'Password123!';
        $owner = $this->createUser($email, $password);
        $city = $this->createCity('Minsk Delete Draft', 'minsk-delete-draft', 'г. Минск');
        $property = $this->createProperty($owner, $city, 'draft');
        $propertyId = $property->getId()->getValue();

        $token = $this->loginAndGetToken($email, $password);
        if ($token === '') {
            self::markTestSkipped('Could not obtain JWT token.');
        }

        $this->client->request(
            'DELETE',
            '/api/properties/' . $propertyId,
            server: ['HTTP_AUTHORIZATION' => 'Bearer ' . $token],
        );

        self::assertSame(200, $this->client->getResponse()->getStatusCode());

        $this->entityManager()->clear();
        $deleted = $this->entityManager()->find(Property::class, $propertyId);
        self::assertNotNull($deleted);
        self::assertSame('deleted', $deleted->getStatus());
    }

    public function testDeletePublishedWithoutArchiveReturnsBadRequest(): void
    {
        $email = 'owner-delete-published@example.com';
        $password = 'Password123!';
        $owner = $this->createUser($email, $password);
        $city = $this->createCity('Minsk Delete Published', 'minsk-delete-published', 'г. Минск');
        $property = $this->createProperty($owner, $city, 'published');

        $token = $this->loginAndGetToken($email, $password);
        if ($token === '') {
            self::markTestSkipped('Could not obtain JWT token.');
        }

        $this->client->request(
            'DELETE',
            '/api/properties/' . $property->getId()->getValue(),
            server: ['HTTP_AUTHORIZATION' => 'Bearer ' . $token],
        );

        self::assertSame(400, $this->client->getResponse()->getStatusCode());
    }

    public function testDeleteArchivedBefore30DaysReturnsBadRequest(): void
    {
        $email = 'owner-delete-soon@example.com';
        $password = 'Password123!';
        $owner = $this->createUser($email, $password);
        $city = $this->createCity('Minsk Delete Soon', 'minsk-delete-soon', 'г. Минск');
        $property = $this->createProperty($owner, $city, 'published');
        $property->archive();
        $this->entityManager()->flush();

        $token = $this->loginAndGetToken($email, $password);
        if ($token === '') {
            self::markTestSkipped('Could not obtain JWT token.');
        }

        $this->client->request(
            'DELETE',
            '/api/properties/' . $property->getId()->getValue(),
            server: ['HTTP_AUTHORIZATION' => 'Bearer ' . $token],
        );

        self::assertSame(400, $this->client->getResponse()->getStatusCode());
    }

    public function testDeleteArchivedAfter30DaysAsOwner(): void
    {
        $email = 'owner-delete-old-archive@example.com';
        $password = 'Password123!';
        $owner = $this->createUser($email, $password);
        $city = $this->createCity('Minsk Delete Old', 'minsk-delete-old', 'г. Минск');
        $property = $this->createProperty($owner, $city, 'published');
        $this->setPropertyArchivedAt($property, new \DateTimeImmutable('-31 days'));
        $propertyId = $property->getId()->getValue();

        $token = $this->loginAndGetToken($email, $password);
        if ($token === '') {
            self::markTestSkipped('Could not obtain JWT token.');
        }

        $this->client->request(
            'DELETE',
            '/api/properties/' . $propertyId,
            server: ['HTTP_AUTHORIZATION' => 'Bearer ' . $token],
        );

        self::assertSame(200, $this->client->getResponse()->getStatusCode());

        $this->entityManager()->clear();
        $deleted = $this->entityManager()->find(Property::class, $propertyId);
        self::assertNotNull($deleted);
        self::assertSame('deleted', $deleted->getStatus());
    }

    public function testArchiveDraftReturnsBadRequest(): void
    {
        $email = 'owner-archive-draft@example.com';
        $password = 'Password123!';
        $owner = $this->createUser($email, $password);
        $city = $this->createCity('Minsk Archive Draft', 'minsk-archive-draft', 'г. Минск');
        $property = $this->createProperty($owner, $city, 'draft');

        $token = $this->loginAndGetToken($email, $password);
        if ($token === '') {
            self::markTestSkipped('Could not obtain JWT token.');
        }

        $this->client->request(
            'POST',
            '/api/properties/' . $property->getId()->getValue() . '/archive',
            server: ['HTTP_AUTHORIZATION' => 'Bearer ' . $token],
        );

        self::assertSame(400, $this->client->getResponse()->getStatusCode());
    }

    public function testUnarchivePublishedReturnsBadRequest(): void
    {
        $email = 'owner-unarchive-published@example.com';
        $password = 'Password123!';
        $owner = $this->createUser($email, $password);
        $city = $this->createCity('Minsk Unarchive Pub', 'minsk-unarchive-pub', 'г. Минск');
        $property = $this->createProperty($owner, $city, 'published');

        $token = $this->loginAndGetToken($email, $password);
        if ($token === '') {
            self::markTestSkipped('Could not obtain JWT token.');
        }

        $this->client->request(
            'POST',
            '/api/properties/' . $property->getId()->getValue() . '/unarchive',
            server: ['HTTP_AUTHORIZATION' => 'Bearer ' . $token],
        );

        self::assertSame(400, $this->client->getResponse()->getStatusCode());
    }
}
