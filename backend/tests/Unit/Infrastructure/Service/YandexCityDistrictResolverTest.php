<?php

declare(strict_types=1);

namespace App\Tests\Unit\Infrastructure\Service;

use App\Domain\Property\Entity\City;
use App\Domain\Property\Entity\CityDistrict;
use App\Domain\Property\Entity\PropertyGeocoderResult;
use App\Domain\Property\Repository\CityDistrictRepositoryInterface;
use App\Domain\Property\Repository\CityRepositoryInterface;
use App\Domain\Property\Repository\PropertyGeocoderResultRepositoryInterface;
use App\Infrastructure\Service\YandexCityDistrictResolver;
use PHPUnit\Framework\TestCase;
use Psr\Log\LoggerInterface;
use Symfony\Contracts\HttpClient\HttpClientInterface;
use Symfony\Contracts\HttpClient\ResponseInterface;

final class YandexCityDistrictResolverTest extends TestCase
{
    public function testReturnsNullWhenApiKeyIsEmpty(): void
    {
        $resolver = $this->createResolver(apiKey: '');

        self::assertNull($resolver->resolve(53.9, 27.56, 1));
    }

    public function testReturnsNullWhenCityDoesNotSupportDistricts(): void
    {
        $city = $this->createCityStub(99, 'grodno-suburb');

        $cityRepository = $this->createStub(CityRepositoryInterface::class);
        $cityRepository->method('findById')->willReturn($city);

        $resolver = $this->createResolver(cityRepository: $cityRepository);

        self::assertNull($resolver->resolve(53.9, 27.56, 99));
    }

    public function testParsesLastDistrictFromGeocoderResponse(): void
    {
        $city = $this->createCityStub(1, 'minsk');

        $cityRepository = $this->createStub(CityRepositoryInterface::class);
        $cityRepository->method('findById')->willReturn($city);

        $response = $this->createStub(ResponseInterface::class);
        $response->method('getStatusCode')->willReturn(200);
        $response->method('toArray')->willReturn($this->createGeocoderResponse());

        $httpClient = $this->createMock(HttpClientInterface::class);
        $httpClient
            ->expects(self::once())
            ->method('request')
            ->with(
                'GET',
                'https://geocode-maps.yandex.ru/v1/',
                self::callback(static function (array $options): bool {
                    return ($options['query']['results'] ?? null) === 10
                        && !isset($options['query']['kind'])
                        && ($options['headers']['Referer'] ?? null) === 'https://posutki.by';
                }),
            )
            ->willReturn($response);

        $existingDistrict = new CityDistrict(1, 'Советский район');
        $idReflection = new \ReflectionProperty($existingDistrict, 'id');
        $idReflection->setAccessible(true);
        $idReflection->setValue($existingDistrict, 42);

        $cityDistrictRepository = $this->createMock(CityDistrictRepositoryInterface::class);
        $cityDistrictRepository
            ->expects(self::once())
            ->method('findByCityIdAndName')
            ->with(1, 'Советский район')
            ->willReturn($existingDistrict);

        $resolver = $this->createResolver(
            httpClient: $httpClient,
            cityRepository: $cityRepository,
            cityDistrictRepository: $cityDistrictRepository,
        );

        $result = $resolver->resolve(53.9, 27.56, 1);

        self::assertSame($existingDistrict, $result);
    }

    public function testStoresGeocoderResponseWhenPropertyIdProvided(): void
    {
        $city = $this->createCityStub(1, 'minsk');

        $cityRepository = $this->createStub(CityRepositoryInterface::class);
        $cityRepository->method('findById')->willReturn($city);

        $geocoderResponse = $this->createGeocoderResponse();

        $response = $this->createStub(ResponseInterface::class);
        $response->method('getStatusCode')->willReturn(200);
        $response->method('toArray')->willReturn($geocoderResponse);

        $httpClient = $this->createStub(HttpClientInterface::class);
        $httpClient->method('request')->willReturn($response);

        $existingDistrict = new CityDistrict(1, 'Советский район');
        $idReflection = new \ReflectionProperty($existingDistrict, 'id');
        $idReflection->setAccessible(true);
        $idReflection->setValue($existingDistrict, 42);

        $cityDistrictRepository = $this->createStub(CityDistrictRepositoryInterface::class);
        $cityDistrictRepository->method('findByCityIdAndName')->willReturn($existingDistrict);

        $propertyGeocoderResultRepository = $this->createMock(PropertyGeocoderResultRepositoryInterface::class);
        $propertyGeocoderResultRepository
            ->expects(self::exactly(2))
            ->method('findByPropertyId')
            ->with(123)
            ->willReturnOnConsecutiveCalls(null, null);
        $propertyGeocoderResultRepository
            ->expects(self::once())
            ->method('save')
            ->with(self::callback(static function (PropertyGeocoderResult $result) use ($geocoderResponse): bool {
                return $result->getPropertyId() === 123
                    && $result->getLatitude() === 53.9
                    && $result->getLongitude() === 27.56
                    && $result->getResponse() === $geocoderResponse;
            }));

        $resolver = $this->createResolver(
            httpClient: $httpClient,
            cityRepository: $cityRepository,
            cityDistrictRepository: $cityDistrictRepository,
            propertyGeocoderResultRepository: $propertyGeocoderResultRepository,
        );

        $resolver->resolve(53.9, 27.56, 1, 123);
    }

    public function testUsesCachedGeocoderResponseWhenCoordinatesMatch(): void
    {
        $city = $this->createCityStub(1, 'minsk');

        $cityRepository = $this->createStub(CityRepositoryInterface::class);
        $cityRepository->method('findById')->willReturn($city);

        $geocoderResponse = $this->createGeocoderResponse();
        $cached = new PropertyGeocoderResult(123, 53.9, 27.56, $geocoderResponse);

        $httpClient = $this->createMock(HttpClientInterface::class);
        $httpClient->expects(self::never())->method('request');

        $existingDistrict = new CityDistrict(1, 'Советский район');
        $idReflection = new \ReflectionProperty($existingDistrict, 'id');
        $idReflection->setAccessible(true);
        $idReflection->setValue($existingDistrict, 42);

        $cityDistrictRepository = $this->createStub(CityDistrictRepositoryInterface::class);
        $cityDistrictRepository->method('findByCityIdAndName')->willReturn($existingDistrict);

        $propertyGeocoderResultRepository = $this->createMock(PropertyGeocoderResultRepositoryInterface::class);
        $propertyGeocoderResultRepository
            ->expects(self::once())
            ->method('findByPropertyId')
            ->with(123)
            ->willReturn($cached);
        $propertyGeocoderResultRepository->expects(self::never())->method('save');

        $resolver = $this->createResolver(
            httpClient: $httpClient,
            cityRepository: $cityRepository,
            cityDistrictRepository: $cityDistrictRepository,
            propertyGeocoderResultRepository: $propertyGeocoderResultRepository,
        );

        $result = $resolver->resolve(53.9, 27.56, 1, 123);

        self::assertSame($existingDistrict, $result);
    }

    public function testForceRefreshBypassesCachedGeocoderResponse(): void
    {
        $city = $this->createCityStub(1, 'minsk');

        $cityRepository = $this->createStub(CityRepositoryInterface::class);
        $cityRepository->method('findById')->willReturn($city);

        $geocoderResponse = $this->createGeocoderResponse();
        $cached = new PropertyGeocoderResult(123, 53.9, 27.56, $geocoderResponse);

        $response = $this->createStub(ResponseInterface::class);
        $response->method('getStatusCode')->willReturn(200);
        $response->method('toArray')->willReturn($geocoderResponse);

        $httpClient = $this->createMock(HttpClientInterface::class);
        $httpClient->expects(self::once())->method('request')->willReturn($response);

        $existingDistrict = new CityDistrict(1, 'Советский район');
        $idReflection = new \ReflectionProperty($existingDistrict, 'id');
        $idReflection->setAccessible(true);
        $idReflection->setValue($existingDistrict, 42);

        $cityDistrictRepository = $this->createStub(CityDistrictRepositoryInterface::class);
        $cityDistrictRepository->method('findByCityIdAndName')->willReturn($existingDistrict);

        $propertyGeocoderResultRepository = $this->createMock(PropertyGeocoderResultRepositoryInterface::class);
        $propertyGeocoderResultRepository
            ->expects(self::once())
            ->method('findByPropertyId')
            ->with(123)
            ->willReturn($cached);
        $propertyGeocoderResultRepository->expects(self::once())->method('save');

        $resolver = $this->createResolver(
            httpClient: $httpClient,
            cityRepository: $cityRepository,
            cityDistrictRepository: $cityDistrictRepository,
            propertyGeocoderResultRepository: $propertyGeocoderResultRepository,
        );

        $resolver->resolve(53.9, 27.56, 1, 123, true);
    }

    public function testReturnsNullOnHttpError(): void
    {
        $city = $this->createCityStub(1, 'minsk');

        $cityRepository = $this->createStub(CityRepositoryInterface::class);
        $cityRepository->method('findById')->willReturn($city);

        $response = $this->createStub(ResponseInterface::class);
        $response->method('getStatusCode')->willReturn(500);

        $httpClient = $this->createStub(HttpClientInterface::class);
        $httpClient->method('request')->willReturn($response);

        $resolver = $this->createResolver(
            httpClient: $httpClient,
            cityRepository: $cityRepository,
        );

        self::assertNull($resolver->resolve(53.9, 27.56, 1));
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
                                        'text' => 'Беларусь, Минск, микрорайон Комаровка',
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
                                        'text' => 'Беларусь, Минск, Советский район',
                                        'Address' => [
                                            'Components' => [
                                                ['kind' => 'locality', 'name' => 'Минск'],
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
        ?HttpClientInterface $httpClient = null,
        ?CityRepositoryInterface $cityRepository = null,
        ?CityDistrictRepositoryInterface $cityDistrictRepository = null,
        ?PropertyGeocoderResultRepositoryInterface $propertyGeocoderResultRepository = null,
        string $apiKey = 'test-key',
        string $referer = 'https://posutki.by',
    ): YandexCityDistrictResolver {
        return new YandexCityDistrictResolver(
            $httpClient ?? $this->createStub(HttpClientInterface::class),
            $cityRepository ?? $this->createStub(CityRepositoryInterface::class),
            $cityDistrictRepository ?? $this->createStub(CityDistrictRepositoryInterface::class),
            $propertyGeocoderResultRepository ?? $this->createStub(PropertyGeocoderResultRepositoryInterface::class),
            $this->createStub(LoggerInterface::class),
            $apiKey,
            $referer,
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
