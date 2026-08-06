<?php

declare(strict_types=1);

namespace App\Tests\Functional\Api;

use App\Domain\Property\Entity\Landmark;
use App\Domain\Property\Entity\PropertyLandmark;

final class LandmarkCatalogTest extends ApiTestCase
{
    public function testListLandmarksByCitySlug(): void
    {
        $city = $this->createCity('Minsk', 'minsk', 'г. Минск');
        $landmark = new Landmark($city->getId(), 'National Library', 'national-library', 53.9315, 27.6458);
        $this->entityManager()->persist($landmark);
        $this->entityManager()->flush();

        $this->client->request('GET', '/api/cities/minsk/landmarks');

        self::assertSame(200, $this->client->getResponse()->getStatusCode());
        $payload = json_decode($this->client->getResponse()->getContent() ?: '', true);
        self::assertIsArray($payload['data'] ?? null);
        self::assertCount(1, $payload['data']);
        self::assertSame('national-library', $payload['data'][0]['slug']);
        self::assertSame('National Library', $payload['data'][0]['name']);
    }

    public function testGetLandmarkByCitySlugAndSlug(): void
    {
        $city = $this->createCity('Minsk', 'minsk', 'г. Минск');
        $landmark = new Landmark($city->getId(), 'National Library', 'national-library', 53.9315, 27.6458);
        $landmark->setShortDescription('Landmark intro');
        $landmark->setCatalogLocationPhrase('возле Национальной библиотеки в Минске');
        $this->entityManager()->persist($landmark);
        $this->entityManager()->flush();

        $this->client->request('GET', '/api/cities/minsk/landmarks/national-library');

        self::assertSame(200, $this->client->getResponse()->getStatusCode());
        $payload = json_decode($this->client->getResponse()->getContent() ?: '', true);
        self::assertSame('national-library', $payload['data']['slug']);
        self::assertSame('Landmark intro', $payload['data']['shortDescription']);
    }

    public function testFilterByLandmarkSlug(): void
    {
        $owner = $this->createUser('landmark-filter@example.com', 'Password123!');
        $city = $this->createCity('Minsk', 'minsk', 'г. Минск');
        $landmark = new Landmark($city->getId(), 'National Library', 'national-library', 53.9315, 27.6458);
        $this->entityManager()->persist($landmark);
        $this->entityManager()->flush();

        $nearLandmark = $this->createProperty($owner, $city, 'published');
        $outsideLandmark = $this->createProperty($owner, $city, 'published');

        $this->entityManager()->persist(new PropertyLandmark(
            $nearLandmark->getId()->getValue(),
            $landmark->getId(),
            0.4,
        ));
        $this->entityManager()->flush();

        $this->client->request(
            'GET',
            '/api/properties?type=apartment&citySlug=minsk&landmarkSlug=national-library',
        );

        self::assertSame(200, $this->client->getResponse()->getStatusCode());
        $ids = $this->idsFromListPayload();
        self::assertContains($nearLandmark->getId()->getValue(), $ids);
        self::assertNotContains($outsideLandmark->getId()->getValue(), $ids);
    }

    public function testFilterByUnknownLandmarkSlugReturns404(): void
    {
        $this->createCity('Minsk', 'minsk', 'г. Минск');

        $this->client->request(
            'GET',
            '/api/properties?type=apartment&citySlug=minsk&landmarkSlug=unknown-landmark',
        );

        self::assertSame(404, $this->client->getResponse()->getStatusCode());
    }
}
