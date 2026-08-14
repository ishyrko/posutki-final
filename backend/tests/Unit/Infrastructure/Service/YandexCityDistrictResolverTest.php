<?php

declare(strict_types=1);

namespace App\Tests\Unit\Infrastructure\Service;

use App\Domain\Property\Entity\City;
use App\Domain\Property\Entity\CityDistrict;
use App\Domain\Property\Repository\CityDistrictRepositoryInterface;
use App\Domain\Property\Repository\CityRepositoryInterface;
use App\Domain\Property\Service\GeocoderResponseProviderInterface;
use App\Infrastructure\Service\CityDistrictSlugGenerator;
use App\Infrastructure\Service\GeocoderPlaceExtractor;
use App\Infrastructure\Service\SlugGenerator;
use App\Infrastructure\Service\YandexCityDistrictResolver;
use PHPUnit\Framework\TestCase;
use Psr\Log\LoggerInterface;

final class YandexCityDistrictResolverTest extends TestCase
{
    public function testReturnsNullWhenGeocoderReturnsNull(): void
    {
        $provider = $this->createMock(GeocoderResponseProviderInterface::class);
        $provider->method('resolve')->willReturn(null);

        $city = $this->createCityStub(1, 'minsk');
        $cityRepository = $this->createStub(CityRepositoryInterface::class);
        $cityRepository->method('findById')->willReturn($city);

        $resolver = $this->createResolver(
            geocoderResponseProvider: $provider,
            cityRepository: $cityRepository,
        );

        self::assertNull($resolver->resolve(53.9, 27.56, 1));
    }

    public function testReturnsNullWhenCityDoesNotSupportDistricts(): void
    {
        $city = $this->createCityStub(99, 'grodno-suburb');

        $cityRepository = $this->createStub(CityRepositoryInterface::class);
        $cityRepository->method('findById')->willReturn($city);

        $provider = $this->createMock(GeocoderResponseProviderInterface::class);
        $provider->expects(self::never())->method('resolve');

        $resolver = $this->createResolver(
            geocoderResponseProvider: $provider,
            cityRepository: $cityRepository,
        );

        self::assertNull($resolver->resolve(53.9, 27.56, 99));
    }

    public function testResolvesAdminDistrictFromGeocoderResponse(): void
    {
        $city = $this->createCityStub(1, 'minsk');

        $cityRepository = $this->createStub(CityRepositoryInterface::class);
        $cityRepository->method('findById')->willReturn($city);

        $provider = $this->createMock(GeocoderResponseProviderInterface::class);
        $provider->method('resolve')->willReturn($this->createGeocoderResponse());

        $existingDistrict = new CityDistrict(1, 'Советский район', 'Советский', 'sovetskiy');
        $idReflection = new \ReflectionProperty($existingDistrict, 'id');
        $idReflection->setAccessible(true);
        $idReflection->setValue($existingDistrict, 42);

        $cityDistrictRepository = $this->createMock(CityDistrictRepositoryInterface::class);
        $cityDistrictRepository
            ->expects(self::once())
            ->method('findByCityIdAndOfficialName')
            ->with(1, 'Советский район')
            ->willReturn($existingDistrict);

        $resolver = $this->createResolver(
            geocoderResponseProvider: $provider,
            cityRepository: $cityRepository,
            cityDistrictRepository: $cityDistrictRepository,
        );

        $result = $resolver->resolve(53.9, 27.56, 1);

        self::assertSame($existingDistrict, $result);
    }

    /**
     * @return array<string, mixed>
     */
    private function createGeocoderResponse(): array
    {
        return [
            'response' => [
                'GeoObjectCollection' => [
                    'featureMember' => [
                        [
                            'GeoObject' => [
                                'metaDataProperty' => [
                                    'GeocoderMetaData' => [
                                        'kind' => 'district',
                                        'Address' => [
                                            'Components' => [
                                                ['kind' => 'district', 'name' => 'микрорайон Комаровка'],
                                            ],
                                        ],
                                    ],
                                ],
                            ],
                        ],
                        [
                            'GeoObject' => [
                                'metaDataProperty' => [
                                    'GeocoderMetaData' => [
                                        'kind' => 'district',
                                        'Address' => [
                                            'Components' => [
                                                ['kind' => 'district', 'name' => 'Советский район'],
                                            ],
                                        ],
                                    ],
                                ],
                            ],
                        ],
                    ],
                ],
            ],
        ];
    }

    private function createResolver(
        ?GeocoderResponseProviderInterface $geocoderResponseProvider = null,
        ?CityRepositoryInterface $cityRepository = null,
        ?CityDistrictRepositoryInterface $cityDistrictRepository = null,
        ?CityDistrictSlugGenerator $cityDistrictSlugGenerator = null,
    ): YandexCityDistrictResolver {
        $districtRepository = $cityDistrictRepository ?? $this->createStub(CityDistrictRepositoryInterface::class);

        return new YandexCityDistrictResolver(
            $cityRepository ?? $this->createStub(CityRepositoryInterface::class),
            $districtRepository,
            $geocoderResponseProvider ?? $this->createStub(GeocoderResponseProviderInterface::class),
            new GeocoderPlaceExtractor(),
            $cityDistrictSlugGenerator ?? new CityDistrictSlugGenerator(
                new SlugGenerator(),
                $districtRepository,
            ),
            $this->createStub(LoggerInterface::class),
        );
    }

    private function createCityStub(int $id, string $slug): City
    {
        $city = $this->createStub(City::class);
        $city->method('getId')->willReturn($id);
        $city->method('getSlug')->willReturn($slug);

        return $city;
    }
}
