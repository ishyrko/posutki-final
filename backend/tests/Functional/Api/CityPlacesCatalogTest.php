<?php

declare(strict_types=1);

namespace App\Tests\Functional\Api;

use App\Domain\Property\Entity\CityMicrodistrict;
use App\Domain\Property\Entity\ResidentialComplex;

final class CityPlacesCatalogTest extends ApiTestCase
{
    public function testListPlacesByCitySlug(): void
    {
        $city = $this->createCity('Minsk', 'minsk', 'г. Минск');
        $micro = new CityMicrodistrict($city->getId(), 'микрорайон Уручье', 'Уручье', 'Уручье', 'uruchye');
        $complex = new ResidentialComplex($city->getId(), 'ЖК Маяк Минска', 'Маяк Минска', 'Маяке Минска', 'mayak-minska');
        $this->entityManager()->persist($micro);
        $this->entityManager()->persist($complex);
        $this->entityManager()->flush();

        $this->client->request('GET', '/api/cities/minsk/places');

        self::assertSame(200, $this->client->getResponse()->getStatusCode());

        $payload = json_decode($this->client->getResponse()->getContent() ?: '', true);
        self::assertTrue($payload['success']);
        self::assertCount(2, $payload['data']);

        $types = array_column($payload['data'], 'type');
        self::assertContains('microdistrict', $types);
        self::assertContains('residential_complex', $types);
    }

    public function testFilterByMicrodistrictSlug(): void
    {
        $owner = $this->createUser('micro-filter@example.com', 'Password123!');
        $city = $this->createCity('Minsk', 'minsk', 'г. Минск');
        $micro = new CityMicrodistrict($city->getId(), 'микрорайон Уручье', 'Уручье', 'Уручье', 'uruchye');
        $this->entityManager()->persist($micro);
        $this->entityManager()->flush();

        $inMicro = $this->createProperty($owner, $city, 'published');
        $inMicro->setCityMicrodistrictId($micro->getId());
        $outside = $this->createProperty($owner, $city, 'published');
        $this->entityManager()->flush();

        $this->client->request(
            'GET',
            '/api/properties?type=apartment&citySlug=minsk&microdistrictSlug=uruchye',
        );

        self::assertSame(200, $this->client->getResponse()->getStatusCode());

        $ids = $this->idsFromListPayload();
        self::assertContains($inMicro->getId()->getValue(), $ids);
        self::assertNotContains($outside->getId()->getValue(), $ids);
    }
}
