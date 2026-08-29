<?php

declare(strict_types=1);

namespace App\Tests\Unit\Domain\Property\Service;

use App\Domain\Property\Entity\City;
use App\Domain\Property\Entity\Region;
use App\Domain\Property\Entity\RegionDistrict;
use App\Domain\Property\Entity\PropertyPlacementLevelPrice;
use App\Domain\Property\Repository\CityRepositoryInterface;
use App\Domain\Property\Repository\PropertyPlacementLevelPriceRepositoryInterface;
use App\Domain\Property\Service\ApartmentPlacementScopeResolver;
use PHPUnit\Framework\TestCase;

final class ApartmentPlacementScopeResolverTest extends TestCase
{
    public function testPrefixCatalogCityUsesOwnCityScope(): void
    {
        $zhodino = $this->createCity(100, 'zhodino', 'Жодино', isMain: false, isApartmentCatalog: true);
        $levelPrice = new PropertyPlacementLevelPrice('apartment', 100, null, 1, 35);

        $levelPriceRepository = $this->createMock(PropertyPlacementLevelPriceRepositoryInterface::class);
        $levelPriceRepository->method('findActiveByCityId')->with(100)->willReturn([$levelPrice]);

        $cityRepository = $this->createStub(CityRepositoryInterface::class);
        $cityRepository->method('findApartmentCatalog')->willReturn([$zhodino]);

        $resolver = new ApartmentPlacementScopeResolver($cityRepository, $levelPriceRepository);
        $scope = $resolver->resolve($zhodino);

        self::assertSame(100, $scope->tariffCityId);
        self::assertSame(100, $scope->catalogCityId);
        self::assertNull($scope->catalogRegionId);
        self::assertSame('Жодино', $scope->locationLabel);
        self::assertSame([], $scope->excludeCitySlugs);
    }

    public function testSatelliteCityInheritsRegionalCenterScope(): void
    {
        $region = $this->createRegion(5, 'minsk', 'Минск');
        $minsk = $this->createCity(1, 'minsk', 'Минск', isMain: true, isApartmentCatalog: true, region: $region);
        $zaslavl = $this->createCity(22763, 'zaslavl', 'Заславль', isMain: false, isApartmentCatalog: false, region: $region);
        $zhodino = $this->createCity(100, 'zhodino', 'Жодино', isMain: false, isApartmentCatalog: true, region: $region);
        $minskLevel = new PropertyPlacementLevelPrice('apartment', 1, null, 1, 35);

        $levelPriceRepository = $this->createMock(PropertyPlacementLevelPriceRepositoryInterface::class);
        $levelPriceRepository->method('findActiveByCityId')->willReturnCallback(
            static fn (int $cityId): array => $cityId === 1 ? [$minskLevel] : [],
        );

        $cityRepository = $this->createMock(CityRepositoryInterface::class);
        $cityRepository->method('findApartmentCatalog')->willReturn([$minsk, $zhodino]);
        $cityRepository->method('findMainCityByRegionId')->with(5)->willReturn($minsk);

        $resolver = new ApartmentPlacementScopeResolver($cityRepository, $levelPriceRepository);
        $scope = $resolver->resolve($zaslavl);

        self::assertSame(1, $scope->tariffCityId);
        self::assertNull($scope->catalogCityId);
        self::assertSame(5, $scope->catalogRegionId);
        self::assertSame(['zhodino'], $scope->excludeCitySlugs);
        self::assertSame('Минск и район', $scope->locationLabel);
    }

    public function testMainRegionalCenterUsesRegionalCatalogScope(): void
    {
        $region = $this->createRegion(5, 'minsk', 'Минск');
        $minsk = $this->createCity(1, 'minsk', 'Минск', isMain: true, isApartmentCatalog: true, region: $region);
        $zhodino = $this->createCity(100, 'zhodino', 'Жодино', isMain: false, isApartmentCatalog: true, region: $region);
        $minskLevel = new PropertyPlacementLevelPrice('apartment', 1, null, 1, 35);

        $levelPriceRepository = $this->createMock(PropertyPlacementLevelPriceRepositoryInterface::class);
        $levelPriceRepository->method('findActiveByCityId')->with(1)->willReturn([$minskLevel]);

        $cityRepository = $this->createMock(CityRepositoryInterface::class);
        $cityRepository->method('findApartmentCatalog')->willReturn([$minsk, $zhodino]);

        $resolver = new ApartmentPlacementScopeResolver($cityRepository, $levelPriceRepository);
        $scope = $resolver->resolve($minsk);

        self::assertSame(1, $scope->tariffCityId);
        self::assertNull($scope->catalogCityId);
        self::assertSame(5, $scope->catalogRegionId);
        self::assertSame(['zhodino'], $scope->excludeCitySlugs);
        self::assertSame('Минск и район', $scope->locationLabel);
    }

    private function createRegion(int $id, string $slug, string $name): Region
    {
        $region = new Region();
        $this->setPrivate($region, 'id', $id);
        $this->setPrivate($region, 'slug', $slug);
        $this->setPrivate($region, 'name', $name);

        return $region;
    }

    private function createCity(
        int $id,
        string $slug,
        string $name,
        bool $isMain,
        bool $isApartmentCatalog,
        ?Region $region = null,
    ): City {
        $city = new City();
        $this->setPrivate($city, 'id', $id);
        $this->setPrivate($city, 'slug', $slug);
        $this->setPrivate($city, 'name', $name);
        $this->setPrivate($city, 'shortName', $name);
        $this->setPrivate($city, 'isMain', $isMain);
        $this->setPrivate($city, 'isApartmentCatalog', $isApartmentCatalog);

        if ($region !== null) {
            $district = new RegionDistrict();
            $this->setPrivate($district, 'region', $region);
            $this->setPrivate($city, 'regionDistrict', $district);
        }

        return $city;
    }

    private function setPrivate(object $object, string $property, mixed $value): void
    {
        $reflection = new \ReflectionProperty($object, $property);
        $reflection->setAccessible(true);
        $reflection->setValue($object, $value);
    }
}
