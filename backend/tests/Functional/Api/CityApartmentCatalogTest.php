<?php

declare(strict_types=1);

namespace App\Tests\Functional\Api;

final class CityApartmentCatalogTest extends ApiTestCase
{
    public function testApartmentCatalogCitiesReturnsOrderedCatalogCities(): void
    {
        $this->createCity('Жодино', 'zhodino', 'г. Жодино');

        $minsk = $this->createCity('Минск', 'minsk', 'г. Минск', null, false, true, true);
        $vitebsk = $this->createCity('Витебск', 'vitebsk', 'г. Витебск', null, false, true, true);
        $brest = $this->createCity('Брест', 'brest', 'г. Брест', null, false, true, true);
        $baranovichi = $this->createCity('Барановичи', 'baranovichi', 'г. Барановичи', null, false, false, true);
        $this->createCity('Борисов', 'borisov', 'г. Борисов', null, true, false, false);

        $this->client->request('GET', '/api/address/cities/apartment-catalog');

        self::assertSame(200, $this->client->getResponse()->getStatusCode());
        $payload = json_decode($this->client->getResponse()->getContent() ?: '', true);
        self::assertIsArray($payload['data'] ?? null);
        self::assertCount(4, $payload['data']['cities']);
        self::assertSame(['baranovichi'], $payload['data']['prefixSlugs']);
        self::assertSame(['minsk', 'brest', 'vitebsk', 'baranovichi'], $payload['data']['catalogSlugs']);

        $slugs = array_column($payload['data']['cities'], 'slug');
        self::assertSame(['minsk', 'brest', 'vitebsk', 'baranovichi'], $slugs);
        self::assertTrue($payload['data']['cities'][0]['isMain']);
        self::assertSame($minsk->getId(), $payload['data']['cities'][0]['id']);
        self::assertNotContains('borisov', $slugs);
    }

    public function testHomeCityApartmentCountsUsesApartmentCatalogCities(): void
    {
        $owner = $this->createUser('catalog-counts@example.com');
        $region = $this->createRegion('minsk', 'Минская область');
        $district = $this->createRegionDistrict($region);

        $minsk = $this->createCity('Минск', 'minsk', 'г. Минск', $district, false, true, true);
        $baranovichi = $this->createCity('Барановичи', 'baranovichi', 'г. Барановичи', $district, false, false, true);

        $this->createProperty($owner, $minsk, 'published', ['type' => 'apartment']);
        $this->createProperty($owner, $baranovichi, 'published', ['type' => 'apartment']);

        $this->client->request('GET', '/api/properties/home-city-apartment-counts');

        self::assertSame(200, $this->client->getResponse()->getStatusCode());
        $payload = json_decode($this->client->getResponse()->getContent() ?: '', true);
        self::assertSame(1, $payload['data']['minsk']);
        self::assertSame(1, $payload['data']['baranovichi']);
    }
}
