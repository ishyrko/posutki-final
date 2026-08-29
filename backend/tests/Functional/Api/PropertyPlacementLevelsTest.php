<?php

declare(strict_types=1);

namespace App\Tests\Functional\Api;

use App\Domain\Property\Entity\PropertyPlacementLevelPrice;
use App\Domain\Property\Entity\PropertyPlacementScopeSettings;

final class PropertyPlacementLevelsTest extends ApiTestCase
{
    public function testSatelliteCityListingInheritsRegionalPlacementLevels(): void
    {
        $owner = $this->createUser('owner-placement@example.com');
        $region = $this->createRegion('minsk', 'Минск');
        $district = $this->createRegionDistrict($region, 'minskiy', 'Минский');
        $minsk = $this->createCity(
            name: 'Минск',
            slug: 'minsk',
            shortName: 'г. Минск',
            regionDistrict: $district,
            isMain: true,
            isApartmentCatalog: true,
        );
        $zaslavl = $this->createCity(
            name: 'Заславль',
            slug: 'zaslavl',
            shortName: 'г. Заславль',
            regionDistrict: $district,
            isMain: false,
            isApartmentCatalog: false,
        );

        $scope = new PropertyPlacementScopeSettings('apartment', $minsk->getId(), null, 5);
        $this->entityManager()->persist($scope);

        $levelPrice = new PropertyPlacementLevelPrice('apartment', $minsk->getId(), null, 1, 35);
        $this->entityManager()->persist($levelPrice);
        $this->entityManager()->flush();

        $property = $this->createProperty($owner, $zaslavl);

        $token = $this->loginAndGetToken('owner-placement@example.com', 'Password123!');
        $this->client->request(
            'GET',
            '/api/properties/' . $property->getId()->getValue() . '/placement-levels',
            server: ['HTTP_AUTHORIZATION' => 'Bearer ' . $token],
        );

        self::assertResponseIsSuccessful();
        $payload = json_decode($this->client->getResponse()->getContent() ?: '', true);
        self::assertTrue($payload['success'] ?? false);
        self::assertSame('Минск и район', $payload['data']['scope']['locationLabel']);
        self::assertSame($minsk->getId(), $payload['data']['scope']['tariffCityId']);
        self::assertCount(1, $payload['data']['levels']);
        self::assertSame(1, $payload['data']['levels'][0]['level']);
        self::assertSame(35, $payload['data']['levels'][0]['priceBynPerMonth']);
    }
}
