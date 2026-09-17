<?php

declare(strict_types=1);

namespace App\Tests\Functional\Api;

use App\Domain\Property\Entity\PropertyDailyStat;

final class MyPropertyStatsControllerTest extends ApiTestCase
{
    public function testRequiresAuthorization(): void
    {
        $this->client->request('GET', '/api/properties/my/stats');

        self::assertSame(401, $this->client->getResponse()->getStatusCode());
    }

    public function testOwnerSeesOnlyOwnPropertyTotals(): void
    {
        $password = 'Password123!';
        $owner = $this->createUser('owner-stats@example.com', $password);
        $other = $this->createUser('other-stats@example.com', $password);
        $city = $this->createCity('Minsk Stats', 'minsk-stats', 'г. Минск');

        $ownProperty = $this->createProperty($owner, $city, 'published');
        $otherProperty = $this->createProperty($other, $city, 'published');

        $today = (new \DateTimeImmutable('today'))->setTime(0, 0);
        $this->insertDailyStat($ownProperty->getId()->getValue(), $today, 10, 2);
        $this->insertDailyStat($otherProperty->getId()->getValue(), $today, 50, 9);

        $token = $this->loginAndGetToken('owner-stats@example.com', $password);
        if ($token === '') {
            self::markTestSkipped('Could not obtain JWT token.');
        }

        $this->client->request(
            'GET',
            '/api/properties/my/stats?period=7',
            server: ['HTTP_AUTHORIZATION' => 'Bearer ' . $token],
        );

        self::assertSame(200, $this->client->getResponse()->getStatusCode());
        $payload = json_decode((string) $this->client->getResponse()->getContent(), true, 512, JSON_THROW_ON_ERROR);
        self::assertTrue($payload['success']);
        self::assertSame(7, $payload['data']['period']);
        self::assertSame(1, $payload['data']['propertiesCount']);
        self::assertSame(10, $payload['data']['totals']['views']);
        self::assertSame(2, $payload['data']['totals']['phoneViews']);
        self::assertCount(7, $payload['data']['daily']);
    }

    public function testInvalidPeriodFallsBackToThirtyDays(): void
    {
        $password = 'Password123!';
        $this->createUser('owner-stats-period@example.com', $password);

        $token = $this->loginAndGetToken('owner-stats-period@example.com', $password);
        if ($token === '') {
            self::markTestSkipped('Could not obtain JWT token.');
        }

        $this->client->request(
            'GET',
            '/api/properties/my/stats?period=14',
            server: ['HTTP_AUTHORIZATION' => 'Bearer ' . $token],
        );

        self::assertSame(200, $this->client->getResponse()->getStatusCode());
        $payload = json_decode((string) $this->client->getResponse()->getContent(), true, 512, JSON_THROW_ON_ERROR);
        self::assertTrue($payload['success']);
        self::assertSame(30, $payload['data']['period']);
        self::assertCount(30, $payload['data']['daily']);
    }

    private function insertDailyStat(int $propertyId, \DateTimeImmutable $date, int $views, int $phoneViews): void
    {
        $stat = new PropertyDailyStat($propertyId, $date);
        for ($i = 0; $i < $views; $i++) {
            $stat->incrementViews();
        }
        for ($i = 0; $i < $phoneViews; $i++) {
            $stat->incrementPhoneViews();
        }

        $this->entityManager()->persist($stat);
        $this->entityManager()->flush();
    }
}
