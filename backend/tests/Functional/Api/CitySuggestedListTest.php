<?php

declare(strict_types=1);

namespace App\Tests\Functional\Api;

final class CitySuggestedListTest extends ApiTestCase
{
    public function testSuggestedCitiesReturnsListingSuggestedOrdered(): void
    {
        $region = $this->createRegion('minsk-region', 'Минская область');
        $district = $this->createRegionDistrict($region);

        $minsk = $this->createCity('Минск', 'minsk', 'г. Минск', $district, true);
        $borisov = $this->createCity('Борисов', 'borisov', 'г. Борисов', $district, true, false);
        $brest = $this->createCity('Брест', 'brest', 'г. Брест', null, true);

        $this->createCity('Жодино', 'zhodino', 'г. Жодино');

        $this->client->request('GET', '/api/address/cities/suggested');

        self::assertSame(200, $this->client->getResponse()->getStatusCode());
        $payload = json_decode($this->client->getResponse()->getContent() ?: '', true);
        self::assertIsArray($payload['data'] ?? null);
        self::assertCount(3, $payload['data']);

        $slugs = array_column($payload['data'], 'slug');
        self::assertSame(['minsk', 'brest', 'borisov'], $slugs);
        self::assertSame('Минск', $payload['data'][0]['name']);
        self::assertSame('Минская область', $payload['data'][0]['regionName']);
    }

    public function testSuggestedCitiesReturnsEmptyWhenNoneConfigured(): void
    {
        $this->client->request('GET', '/api/address/cities/suggested');

        self::assertSame(200, $this->client->getResponse()->getStatusCode());
        $payload = json_decode($this->client->getResponse()->getContent() ?: '', true);
        self::assertSame([], $payload['data']);
    }
}
