<?php

declare(strict_types=1);

namespace App\Tests\Unit\Infrastructure\Service;

use App\Domain\Property\Entity\City;
use App\Domain\Property\Entity\Property;
use App\Domain\Property\Entity\Region;
use App\Domain\Property\Entity\RegionDistrict;
use App\Domain\Property\Repository\CityRepositoryInterface;
use App\Domain\Shared\ValueObject\Id;
use App\Infrastructure\Service\FrontendUrlBuilder;
use PHPUnit\Framework\TestCase;

final class FrontendUrlBuilderTest extends TestCase
{
    public function testPublicPropertyForListingUsesCityPrefixForSmorgonApartment(): void
    {
        $builder = new FrontendUrlBuilder('https://posutki.by', $this->cityRepository('smorgon', 'grodno'));

        $property = $this->createStub(Property::class);
        $property->method('getDealType')->willReturn('daily');
        $property->method('getType')->willReturn('apartment');
        $property->method('getId')->willReturn(Id::fromInt(42));
        $property->method('getCityId')->willReturn(18868);

        self::assertSame(
            'https://posutki.by/smorgon/kvartiry/42/',
            $builder->publicPropertyForListing($property),
        );
    }

    public function testPublicPropertyForListingUsesRegionForSmorgonHouse(): void
    {
        $builder = new FrontendUrlBuilder('https://posutki.by', $this->cityRepository('smorgon', 'grodno'));

        $property = $this->createStub(Property::class);
        $property->method('getDealType')->willReturn('daily');
        $property->method('getType')->willReturn('house');
        $property->method('getId')->willReturn(Id::fromInt(42));
        $property->method('getCityId')->willReturn(18868);

        self::assertSame(
            'https://posutki.by/grodno/doma/42/',
            $builder->publicPropertyForListing($property),
        );
    }

    public function testPublicPropertyForListingUsesRegionForOblastCenter(): void
    {
        $builder = new FrontendUrlBuilder('https://posutki.by', $this->cityRepository('gomel', 'gomel'));

        $property = $this->createStub(Property::class);
        $property->method('getDealType')->willReturn('daily');
        $property->method('getType')->willReturn('apartment');
        $property->method('getId')->willReturn(Id::fromInt(7));
        $property->method('getCityId')->willReturn(30215);

        self::assertSame(
            'https://posutki.by/gomel/kvartiry/7/',
            $builder->publicPropertyForListing($property),
        );
    }

    public function testPublicPropertyForListingHasNoPrefixForMinsk(): void
    {
        $builder = new FrontendUrlBuilder('https://posutki.by', $this->cityRepository('minsk', 'minsk'));

        $property = $this->createStub(Property::class);
        $property->method('getDealType')->willReturn('daily');
        $property->method('getType')->willReturn('apartment');
        $property->method('getId')->willReturn(Id::fromInt(1));
        $property->method('getCityId')->willReturn(1);

        self::assertSame(
            'https://posutki.by/kvartiry/1/',
            $builder->publicPropertyForListing($property),
        );
    }

    private function cityRepository(string $citySlug, string $regionSlug): CityRepositoryInterface
    {
        $region = $this->createMock(Region::class);
        $region->method('getSlug')->willReturn($regionSlug);

        $district = $this->createMock(RegionDistrict::class);
        $district->method('getRegion')->willReturn($region);

        $city = $this->createMock(City::class);
        $city->method('getSlug')->willReturn($citySlug);
        $city->method('getRegionDistrict')->willReturn($district);

        $repository = $this->createMock(CityRepositoryInterface::class);
        $repository->method('findById')->willReturn($city);

        return $repository;
    }
}
