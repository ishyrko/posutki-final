<?php

declare(strict_types=1);

namespace App\Infrastructure\Service;

use App\Domain\Property\Entity\CityDistrict;
use App\Domain\Property\Entity\PropertyGeocoderResult;
use App\Domain\Property\Repository\CityDistrictRepositoryInterface;
use App\Domain\Property\Repository\CityRepositoryInterface;
use App\Domain\Property\Repository\PropertyGeocoderResultRepositoryInterface;
use App\Domain\Property\Service\CitiesWithDistricts;
use App\Domain\Property\Service\CityDistrictResolverInterface;
use Psr\Log\LoggerInterface;
use Symfony\Contracts\HttpClient\HttpClientInterface;

final class YandexCityDistrictResolver implements CityDistrictResolverInterface
{
    private const GEOCODER_URL = 'https://geocode-maps.yandex.ru/v1/';

    public function __construct(
        private readonly HttpClientInterface $httpClient,
        private readonly CityRepositoryInterface $cityRepository,
        private readonly CityDistrictRepositoryInterface $cityDistrictRepository,
        private readonly PropertyGeocoderResultRepositoryInterface $propertyGeocoderResultRepository,
        private readonly LoggerInterface $logger,
        private readonly string $apiKey,
        private readonly string $referer,
    ) {
    }

    public function resolve(
        float $latitude,
        float $longitude,
        int $cityId,
        ?int $propertyId = null,
        bool $forceRefresh = false,
    ): ?CityDistrict {
        if ($this->apiKey === '') {
            return null;
        }

        $city = $this->cityRepository->findById($cityId);
        if ($city === null || !CitiesWithDistricts::supportsSlug($city->getSlug())) {
            return null;
        }

        try {
            $data = $this->resolveGeocoderResponse($latitude, $longitude, $propertyId, $forceRefresh);
            if ($data === null) {
                return null;
            }

            $districtName = $this->extractLastDistrictName($data);
            if ($districtName === null || $districtName === '') {
                return null;
            }

            $existing = $this->cityDistrictRepository->findByCityIdAndName($cityId, $districtName);
            if ($existing !== null) {
                return $existing;
            }

            $cityDistrict = new CityDistrict($cityId, $districtName);
            $this->cityDistrictRepository->save($cityDistrict);

            $persisted = $this->cityDistrictRepository->findByCityIdAndName($cityId, $districtName);

            return $persisted ?? $cityDistrict;
        } catch (\Throwable $e) {
            $this->logger->warning('Failed to resolve city district from coordinates', [
                'cityId' => $cityId,
                'propertyId' => $propertyId,
                'latitude' => $latitude,
                'longitude' => $longitude,
                'error' => $e->getMessage(),
            ]);

            return null;
        }
    }

    /**
     * @return array<string, mixed>|null
     */
    private function resolveGeocoderResponse(
        float $latitude,
        float $longitude,
        ?int $propertyId,
        bool $forceRefresh,
    ): ?array {
        if ($propertyId !== null && !$forceRefresh) {
            $cached = $this->loadCachedGeocoderResponse($propertyId, $latitude, $longitude);
            if ($cached !== null) {
                return $cached;
            }
        }

        $data = $this->fetchGeocoderResponse($longitude, $latitude);
        if ($data === null) {
            return null;
        }

        if ($propertyId !== null) {
            $this->storeGeocoderResult($propertyId, $latitude, $longitude, $data);
        }

        return $data;
    }

    /**
     * @return array<string, mixed>|null
     */
    private function loadCachedGeocoderResponse(int $propertyId, float $latitude, float $longitude): ?array
    {
        $cached = $this->propertyGeocoderResultRepository->findByPropertyId($propertyId);
        if ($cached === null || !$cached->matchesCoordinates($latitude, $longitude)) {
            return null;
        }

        return $cached->getResponse();
    }

    /**
     * @return array<string, mixed>|null
     */
    private function fetchGeocoderResponse(float $longitude, float $latitude): ?array
    {
        $response = $this->httpClient->request('GET', self::GEOCODER_URL, [
            'query' => [
                'apikey' => $this->apiKey,
                'geocode' => sprintf('%F,%F', $longitude, $latitude),
                'format' => 'json',
                'lang' => 'ru_RU',
                'results' => 10,
            ],
            'headers' => [
                'Referer' => $this->referer,
            ],
            'timeout' => 3,
        ]);

        if ($response->getStatusCode() !== 200) {
            return null;
        }

        /** @var array<string, mixed> $data */
        $data = $response->toArray(false);

        return $data;
    }

    /**
     * @param array<string, mixed> $data
     */
    private function extractLastDistrictName(array $data): ?string
    {
        $members = $data['response']['GeoObjectCollection']['featureMember'] ?? null;
        if (!is_array($members)) {
            return null;
        }

        $lastDistrictName = null;

        foreach ($members as $member) {
            if (!is_array($member)) {
                continue;
            }

            $meta = $member['GeoObject']['metaDataProperty']['GeocoderMetaData'] ?? null;
            if (!is_array($meta) || ($meta['kind'] ?? null) !== 'district') {
                continue;
            }

            $components = $meta['Address']['Components'] ?? null;
            if (is_array($components)) {
                foreach ($components as $component) {
                    if (!is_array($component)) {
                        continue;
                    }

                    if (($component['kind'] ?? null) === 'district' && isset($component['name']) && is_string($component['name'])) {
                        $lastDistrictName = $component['name'];
                    }
                }
            }

            if ($lastDistrictName === null && isset($meta['text']) && is_string($meta['text'])) {
                $parts = explode(', ', $meta['text']);
                $lastPart = end($parts);
                if (is_string($lastPart) && $lastPart !== '') {
                    $lastDistrictName = $lastPart;
                }
            }
        }

        return $lastDistrictName;
    }

    /**
     * @param array<string, mixed> $data
     */
    private function storeGeocoderResult(int $propertyId, float $latitude, float $longitude, array $data): void
    {
        $existing = $this->propertyGeocoderResultRepository->findByPropertyId($propertyId);
        if ($existing !== null) {
            $existing->update($latitude, $longitude, $data);
            $this->propertyGeocoderResultRepository->save($existing);

            return;
        }

        $result = new PropertyGeocoderResult($propertyId, $latitude, $longitude, $data);
        $this->propertyGeocoderResultRepository->save($result);
    }
}
