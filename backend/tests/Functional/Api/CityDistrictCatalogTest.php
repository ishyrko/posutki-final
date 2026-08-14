<?php

declare(strict_types=1);

namespace App\Tests\Functional\Api;

use App\Domain\Property\Entity\CityDistrict;

final class CityDistrictCatalogTest extends ApiTestCase
{
    public function testListDistrictsByCitySlug(): void
    {
        $city = $this->createCity('Minsk', 'minsk', 'г. Минск');
        $district = new CityDistrict($city->getId(), 'Советский район', 'Советский район', 'sovetskiy');
        $this->entityManager()->persist($district);
        $this->entityManager()->flush();

        $this->client->request('GET', '/api/cities/minsk/districts');

        self::assertSame(200, $this->client->getResponse()->getStatusCode());
        $payload = json_decode($this->client->getResponse()->getContent() ?: '', true);
        self::assertIsArray($payload['data'] ?? null);
        self::assertCount(1, $payload['data']);
        self::assertSame('sovetskiy', $payload['data'][0]['slug']);
        self::assertSame('Советский район', $payload['data'][0]['name']);
    }

    public function testListDistrictsReturnsEmptyForUnsupportedCity(): void
    {
        $this->createCity('Orsha', 'orsha', 'г. Орша');

        $this->client->request('GET', '/api/cities/orsha/districts');

        self::assertSame(200, $this->client->getResponse()->getStatusCode());
        $payload = json_decode($this->client->getResponse()->getContent() ?: '', true);
        self::assertSame([], $payload['data']);
    }

    public function testFilterByCityDistrictSlug(): void
    {
        $owner = $this->createUser('district-filter@example.com', 'Password123!');
        $city = $this->createCity('Minsk', 'minsk', 'г. Минск');
        $district = new CityDistrict($city->getId(), 'Советский район', 'Советский район', 'sovetskiy');
        $this->entityManager()->persist($district);
        $this->entityManager()->flush();

        $inDistrict = $this->createProperty($owner, $city, 'published');
        $inDistrict->setCityDistrictId($district->getId());
        $outsideDistrict = $this->createProperty($owner, $city, 'published');
        $this->entityManager()->flush();

        $this->client->request(
            'GET',
            '/api/properties?type=apartment&citySlug=minsk&cityDistrictSlug=sovetskiy',
        );

        self::assertSame(200, $this->client->getResponse()->getStatusCode());
        $ids = $this->idsFromListPayload();
        self::assertContains($inDistrict->getId()->getValue(), $ids);
        self::assertNotContains($outsideDistrict->getId()->getValue(), $ids);
    }

    public function testFilterByUnknownCityDistrictSlugReturns404(): void
    {
        $this->createCity('Minsk', 'minsk', 'г. Минск');

        $this->client->request(
            'GET',
            '/api/properties?type=apartment&citySlug=minsk&cityDistrictSlug=unknown-district',
        );

        self::assertSame(404, $this->client->getResponse()->getStatusCode());
    }
}
