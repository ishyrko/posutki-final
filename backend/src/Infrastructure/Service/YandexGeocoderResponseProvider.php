<?php

declare(strict_types=1);

namespace App\Infrastructure\Service;

use App\Domain\Property\Entity\PropertyGeocoderResult;
use App\Domain\Property\Repository\PropertyGeocoderResultRepositoryInterface;
use App\Domain\Property\Service\GeocoderResponseProviderInterface;
use Symfony\Contracts\HttpClient\HttpClientInterface;

final class YandexGeocoderResponseProvider implements GeocoderResponseProviderInterface
{
    private const GEOCODER_URL = 'https://geocode-maps.yandex.ru/v1/';

    public function __construct(
        private readonly HttpClientInterface $httpClient,
        private readonly PropertyGeocoderResultRepositoryInterface $propertyGeocoderResultRepository,
        private readonly string $apiKey,
        private readonly string $referer,
    ) {
    }

    /**
     * @return array<string, mixed>|null
     */
    public function resolve(
        float $latitude,
        float $longitude,
        ?int $propertyId = null,
        bool $forceRefresh = false,
    ): ?array {
        if ($this->apiKey === '') {
            return null;
        }

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
