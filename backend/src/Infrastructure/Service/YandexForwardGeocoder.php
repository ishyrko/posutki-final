<?php

declare(strict_types=1);

namespace App\Infrastructure\Service;

use App\Domain\Property\ValueObject\Coordinates;
use Psr\Log\LoggerInterface;
use Symfony\Contracts\HttpClient\HttpClientInterface;

final class YandexForwardGeocoder
{
    private const GEOCODER_URL = 'https://geocode-maps.yandex.ru/v1/';

    public function __construct(
        private readonly HttpClientInterface $httpClient,
        private readonly LoggerInterface $logger,
        private readonly string $apiKey,
        private readonly string $referer,
    ) {
    }

    public function geocodeAddress(string $address): ?Coordinates
    {
        $address = trim($address);
        if ($address === '' || $this->apiKey === '') {
            return null;
        }

        try {
            $response = $this->httpClient->request('GET', self::GEOCODER_URL, [
                'query' => [
                    'apikey' => $this->apiKey,
                    'geocode' => $address,
                    'format' => 'json',
                    'lang' => 'ru_RU',
                    'results' => 1,
                ],
                'headers' => [
                    'Referer' => $this->referer,
                ],
                'timeout' => 5,
            ]);

            if ($response->getStatusCode() !== 200) {
                return null;
            }

            /** @var array<string, mixed> $data */
            $data = $response->toArray(false);

            return $this->extractCoordinates($data);
        } catch (\Throwable $exception) {
            $this->logger->warning('Yandex forward geocoding failed for landmark address', [
                'address' => $address,
                'error' => $exception->getMessage(),
            ]);

            return null;
        }
    }

    /**
     * @param array<string, mixed> $data
     */
    private function extractCoordinates(array $data): ?Coordinates
    {
        $members = $data['response']['GeoObjectCollection']['featureMember'] ?? null;
        if (!is_array($members) || $members === []) {
            return null;
        }

        $first = $members[0] ?? null;
        if (!is_array($first)) {
            return null;
        }

        $pos = $first['GeoObject']['Point']['pos'] ?? null;
        if (!is_string($pos) || trim($pos) === '') {
            return null;
        }

        $parts = preg_split('/\s+/', trim($pos));
        if ($parts === false || count($parts) < 2) {
            return null;
        }

        $longitude = (float) $parts[0];
        $latitude = (float) $parts[1];

        return Coordinates::create($latitude, $longitude);
    }
}
