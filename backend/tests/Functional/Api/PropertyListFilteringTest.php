<?php

declare(strict_types=1);

namespace App\Tests\Functional\Api;

/**
 * HTTP tests for GET /api/properties query filters (SearchProperties → PropertyRepository::applyFilters).
 */
final class PropertyListFilteringTest extends ApiTestCase
{
    public function testFilterByDealTypeDaily(): void
    {
        $owner = $this->createUser('filter-deal@example.com', 'Password123!');
        $city = $this->createCity();
        $a = $this->createProperty($owner, $city, 'published', ['dealType' => 'daily']);
        $b = $this->createProperty($owner, $city, 'published', ['dealType' => 'daily']);

        $this->client->request('GET', '/api/properties?dealType=daily');

        self::assertSame(200, $this->client->getResponse()->getStatusCode());
        $ids = $this->idsFromListPayload();
        self::assertContains($a->getId()->getValue(), $ids);
        self::assertContains($b->getId()->getValue(), $ids);
    }

    public function testFilterByDealTypeSaleReturnsNothingWhenAllDaily(): void
    {
        $owner = $this->createUser('filter-deal-sale@example.com', 'Password123!');
        $city = $this->createCity();
        $this->createProperty($owner, $city, 'published', ['dealType' => 'daily']);

        $this->client->request('GET', '/api/properties?dealType=sale');

        self::assertSame(200, $this->client->getResponse()->getStatusCode());
        $ids = $this->idsFromListPayload();
        self::assertSame([], $ids);
    }

    public function testFilterByPropertyType(): void
    {
        $owner = $this->createUser('filter-type@example.com', 'Password123!');
        $city = $this->createCity();
        $apartment = $this->createProperty($owner, $city, 'published', [
            'type' => 'apartment',
            'landArea' => null,
        ]);
        $house = $this->createProperty($owner, $city, 'published', [
            'type' => 'house',
            'rooms' => 4,
            'area' => 120.0,
            'landArea' => 10.0,
        ]);

        $this->client->request('GET', '/api/properties?type=house');

        self::assertSame(200, $this->client->getResponse()->getStatusCode());
        $ids = $this->idsFromListPayload();
        self::assertContains($house->getId()->getValue(), $ids);
        self::assertNotContains($apartment->getId()->getValue(), $ids);
    }

    public function testFilterByTypesList(): void
    {
        $owner = $this->createUser('filter-types@example.com', 'Password123!');
        $city = $this->createCity();
        $apartment = $this->createProperty($owner, $city, 'published', ['type' => 'apartment']);
        $house = $this->createProperty($owner, $city, 'published', [
            'type' => 'house',
            'rooms' => 5,
            'area' => 120.0,
            'landArea' => 12.0,
        ]);

        $this->client->request('GET', '/api/properties?types=apartment,house');

        self::assertSame(200, $this->client->getResponse()->getStatusCode());
        $ids = $this->idsFromListPayload();
        self::assertContains($apartment->getId()->getValue(), $ids);
        self::assertContains($house->getId()->getValue(), $ids);
    }

    public function testFilterByCitySlug(): void
    {
        $owner = $this->createUser('filter-city@example.com', 'Password123!');
        $cityA = $this->createCity('City A', 'city-alpha', 'г. A');
        $cityB = $this->createCity('City B', 'city-beta', 'г. B');
        $inA = $this->createProperty($owner, $cityA, 'published');
        $inB = $this->createProperty($owner, $cityB, 'published');

        $this->client->request('GET', '/api/properties?citySlug=city-alpha');

        self::assertSame(200, $this->client->getResponse()->getStatusCode());
        $ids = $this->idsFromListPayload();
        self::assertContains($inA->getId()->getValue(), $ids);
        self::assertNotContains($inB->getId()->getValue(), $ids);
    }

    public function testFilterByRegionSlug(): void
    {
        $owner = $this->createUser('filter-region@example.com', 'Password123!');
        $region = $this->createRegion('brest-region', 'Брестская область');
        $district = $this->createRegionDistrict($region, 'brest-district', 'Брестский район');
        $cityInRegion = $this->createCity('Brest', 'brest', 'г. Брест', $district);
        $cityOutside = $this->createCity('Minsk solo', 'minsk-solo', 'г. Минск');

        $inside = $this->createProperty($owner, $cityInRegion, 'published');
        $outside = $this->createProperty($owner, $cityOutside, 'published');

        $this->client->request('GET', '/api/properties?regionSlug=brest-region');

        self::assertSame(200, $this->client->getResponse()->getStatusCode());
        $ids = $this->idsFromListPayload();
        self::assertContains($inside->getId()->getValue(), $ids);
        self::assertNotContains($outside->getId()->getValue(), $ids);
    }

    public function testFilterByRooms(): void
    {
        $owner = $this->createUser('filter-rooms@example.com', 'Password123!');
        $city = $this->createCity();
        $twoRooms = $this->createProperty($owner, $city, 'published', ['rooms' => 2]);
        $threeRooms = $this->createProperty($owner, $city, 'published', ['rooms' => 3]);

        $this->client->request('GET', '/api/properties?rooms=2');

        self::assertSame(200, $this->client->getResponse()->getStatusCode());
        $ids = $this->idsFromListPayload();
        self::assertContains($twoRooms->getId()->getValue(), $ids);
        self::assertNotContains($threeRooms->getId()->getValue(), $ids);
    }

    public function testFilterByMinAndMaxPriceInByn(): void
    {
        $owner = $this->createUser('filter-price@example.com', 'Password123!');
        $city = $this->createCity();
        $low = $this->createProperty($owner, $city, 'published', ['priceAmount' => 10_000_000]);
        $high = $this->createProperty($owner, $city, 'published', ['priceAmount' => 50_000_000]);

        $this->client->request('GET', '/api/properties?minPrice=20000000&maxPrice=60000000&currency=BYN');

        self::assertSame(200, $this->client->getResponse()->getStatusCode());
        $ids = $this->idsFromListPayload();
        self::assertNotContains($low->getId()->getValue(), $ids);
        self::assertContains($high->getId()->getValue(), $ids);
    }

    public function testFilterByMinAndMaxArea(): void
    {
        $owner = $this->createUser('filter-area@example.com', 'Password123!');
        $city = $this->createCity();
        $small = $this->createProperty($owner, $city, 'published', ['area' => 40.0]);
        $large = $this->createProperty($owner, $city, 'published', ['area' => 90.0]);

        $this->client->request('GET', '/api/properties?minArea=60&maxArea=100');

        self::assertSame(200, $this->client->getResponse()->getStatusCode());
        $ids = $this->idsFromListPayload();
        self::assertNotContains($small->getId()->getValue(), $ids);
        self::assertContains($large->getId()->getValue(), $ids);
    }

    public function testFilterNearMetro(): void
    {
        $owner = $this->createUser('filter-metro-flag@example.com', 'Password123!');
        $city = $this->createCity();
        $near = $this->createProperty($owner, $city, 'published', ['nearMetro' => true]);
        $far = $this->createProperty($owner, $city, 'published', ['nearMetro' => false]);

        $this->client->request('GET', '/api/properties?nearMetro=1');

        self::assertSame(200, $this->client->getResponse()->getStatusCode());
        $ids = $this->idsFromListPayload();
        self::assertContains($near->getId()->getValue(), $ids);
        self::assertNotContains($far->getId()->getValue(), $ids);
    }

    public function testFilterByMetroStationId(): void
    {
        $owner = $this->createUser('filter-metro-id@example.com', 'Password123!');
        $city = $this->createCity();
        $station = $this->createMetroStation($city, 501, 'metro-alpha');
        $linked = $this->createProperty($owner, $city, 'published');
        $this->linkPropertyToMetro($linked, $station, 0.4);
        $plain = $this->createProperty($owner, $city, 'published');

        $this->client->request('GET', '/api/properties?metroStationId=' . $station->getId());

        self::assertSame(200, $this->client->getResponse()->getStatusCode());
        $ids = $this->idsFromListPayload();
        self::assertContains($linked->getId()->getValue(), $ids);
        self::assertNotContains($plain->getId()->getValue(), $ids);
    }

    /**
     * @return list<int>
     */
    private function idsFromListPayload(): array
    {
        $payload = json_decode((string) $this->client->getResponse()->getContent(), true, 512, JSON_THROW_ON_ERROR);
        self::assertTrue($payload['success']);
        self::assertIsArray($payload['data']);

        $ids = [];
        foreach ($payload['data'] as $row) {
            self::assertIsArray($row);
            self::assertArrayHasKey('id', $row);
            $ids[] = (int) $row['id'];
        }

        return $ids;
    }
}
