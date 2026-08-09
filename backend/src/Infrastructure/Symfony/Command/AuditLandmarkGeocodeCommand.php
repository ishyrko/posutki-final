<?php

declare(strict_types=1);

namespace App\Infrastructure\Symfony\Command;

use Doctrine\DBAL\Connection;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;
use Symfony\Contracts\HttpClient\HttpClientInterface;

#[AsCommand(
    name: 'app:audit-landmark-geocode',
    description: 'Compare landmark coordinates/addresses with Yandex Geocoder',
)]
final class AuditLandmarkGeocodeCommand extends Command
{
    private const GEOCODER_URL = 'https://geocode-maps.yandex.ru/v1/';

    public function __construct(
        private readonly Connection $connection,
        private readonly HttpClientInterface $httpClient,
        private readonly string $apiKey,
        private readonly string $referer,
    ) {
        parent::__construct();
    }

    protected function configure(): void
    {
        $this
            ->addOption('apply', null, InputOption::VALUE_NONE, 'Apply Yandex name-based coordinates/addresses to DB')
            ->addOption('threshold', null, InputOption::VALUE_REQUIRED, 'Meters above which coordinates are updated when --apply', '250')
            ->addOption('json-out', null, InputOption::VALUE_REQUIRED, 'Write full report JSON to path');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);
        if ($this->apiKey === '') {
            $io->error('YANDEX_GEOCODER_API_KEY is empty');

            return Command::FAILURE;
        }

        $apply = (bool) $input->getOption('apply');
        $threshold = max(1, (int) $input->getOption('threshold'));
        $jsonOut = $input->getOption('json-out');

        $rows = $this->connection->fetchAllAssociative(
            'SELECT c.slug AS city_slug, c.name AS city_name, l.id, l.slug, l.name, l.latitude, l.longitude, l.address
             FROM landmarks l
             INNER JOIN cities c ON c.id = l.city_id
             ORDER BY c.slug, l.sort_order, l.name'
        );

        $report = [];
        $counts = ['OK' => 0, 'WARN' => 0, 'BAD' => 0, 'MISS' => 0];
        $updated = 0;

        foreach ($rows as $row) {
            $city = (string) $row['city_name'];
            $name = (string) $row['name'];
            $address = trim((string) ($row['address'] ?? ''));
            $lat = $row['latitude'] !== null ? (float) $row['latitude'] : null;
            $lng = $row['longitude'] !== null ? (float) $row['longitude'] : null;

            $nameQuery = $name . ', ' . $city . ', Беларусь';
            // Prefer org/toponym phrasing that Yandex resolves better than bare marketing names.
            $altNameQuery = match (true) {
                str_contains(mb_strtolower($name), 'минск-арена') => 'Минск-Арена спортивный комплекс, Минск, Беларусь',
                str_contains(mb_strtolower($name), 'галилео') => 'торговый центр Галилео, Минск, Беларусь',
                str_contains(mb_strtolower($name), 'dana') => 'Dana Mall, Минск, Беларусь',
                default => null,
            };
            $addressQuery = null;
            if ($address !== '') {
                $addressQuery = $address;
                if (!str_contains(mb_strtolower($address), mb_strtolower($city))) {
                    $addressQuery .= ', ' . $city;
                }
                $addressQuery .= ', Беларусь';
            }

            $nameGeo = $this->geocode($nameQuery);
            usleep(120000);
            if ($altNameQuery !== null) {
                $altGeo = $this->geocode($altNameQuery);
                usleep(120000);
                if ($altGeo !== null && $this->isWeakToponym($nameGeo, $city)) {
                    $nameGeo = $altGeo;
                } elseif ($altGeo !== null && $nameGeo !== null) {
                    // Keep the more specific (non-city) hit.
                    if ($this->isWeakToponym($nameGeo, $city) || !$this->isWeakToponym($altGeo, $city)) {
                        $nameGeo = $altGeo;
                    }
                } elseif ($altGeo !== null) {
                    $nameGeo = $altGeo;
                }
            }
            $addressGeo = null;
            if ($addressQuery !== null) {
                $addressGeo = $this->geocode($addressQuery);
                usleep(120000);
            }

            $chosen = $this->chooseBest($nameGeo, $addressGeo, $lat, $lng, $city);
            $dist = null;
            if ($chosen !== null && $lat !== null && $lng !== null) {
                $dist = self::haversineM($lat, $lng, $chosen['lat'], $chosen['lng']);
            }

            $flag = 'MISS';
            if ($chosen !== null && $dist !== null) {
                if ($dist < 250) {
                    $flag = 'OK';
                } elseif ($dist < 800) {
                    $flag = 'WARN';
                } else {
                    $flag = 'BAD';
                }
            }

            ++$counts[$flag];

            $item = [
                'flag' => $flag,
                'id' => (int) $row['id'],
                'city' => (string) $row['city_slug'],
                'slug' => (string) $row['slug'],
                'name' => $name,
                'our_lat' => $lat,
                'our_lng' => $lng,
                'our_address' => $address,
                'y_lat' => $chosen['lat'] ?? null,
                'y_lng' => $chosen['lng'] ?? null,
                'y_address' => $chosen['address'] ?? null,
                'source' => $chosen['source'] ?? null,
                'dist_m' => $dist === null ? null : (int) round($dist),
                'name_geo' => $nameGeo,
                'address_geo' => $addressGeo,
            ];
            $report[] = $item;

            $io->writeln(sprintf(
                '%s  %-14s %-40s dist=%s src=%s',
                $flag,
                $item['city'],
                $item['slug'],
                $item['dist_m'] === null ? '-' : $item['dist_m'] . 'm',
                (string) ($item['source'] ?? '-')
            ));

            if (!$apply || $chosen === null) {
                continue;
            }

            // Never apply city-centroid fallbacks.
            if ($this->isWeakToponym([
                'lat' => $chosen['lat'],
                'lng' => $chosen['lng'],
                'address' => $chosen['address'],
                'name' => $chosen['name'] ?? '',
            ], $city) && !$this->looksLikeStreetAddress($chosen['address'])) {
                continue;
            }

            $shouldUpdateCoords = $dist === null || $dist >= $threshold;
            $shouldUpdateAddress = $address === ''
                || $this->addressLooksWeak($address)
                || ($shouldUpdateCoords && $this->looksLikeStreetAddress($chosen['address']));

            if (!$shouldUpdateCoords && !$shouldUpdateAddress) {
                continue;
            }

            $newLat = $shouldUpdateCoords ? $chosen['lat'] : $lat;
            $newLng = $shouldUpdateCoords ? $chosen['lng'] : $lng;
            $newAddress = $shouldUpdateAddress
                ? $this->shortenYandexAddress($chosen['address'], $city)
                : $address;

            $this->connection->executeStatement(
                'UPDATE landmarks SET latitude = ?, longitude = ?, address = ? WHERE id = ?',
                [$newLat, $newLng, $newAddress, (int) $row['id']]
            );
            ++$updated;
            $io->writeln(sprintf(
                '  -> updated id=%d to %.6f,%.6f | %s',
                (int) $row['id'],
                (float) $newLat,
                (float) $newLng,
                $newAddress
            ));
        }

        if (is_string($jsonOut) && $jsonOut !== '') {
            file_put_contents($jsonOut, json_encode($report, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT));
            $io->success('Report written to ' . $jsonOut);
        }

        $io->table(array_keys($counts), [array_values($counts)]);
        if ($apply) {
            $io->success(sprintf('Updated %d landmarks (threshold=%dm)', $updated, $threshold));
        }

        return Command::SUCCESS;
    }

    /**
     * @return array{lat: float, lng: float, address: string, name: string}|null
     */
    private function geocode(string $query): ?array
    {
        try {
            $response = $this->httpClient->request('GET', self::GEOCODER_URL, [
                'query' => [
                    'apikey' => $this->apiKey,
                    'geocode' => $query,
                    'format' => 'json',
                    'lang' => 'ru_RU',
                    'results' => 1,
                ],
                'headers' => [
                    'Referer' => $this->referer,
                ],
                'timeout' => 8,
            ]);
            if ($response->getStatusCode() !== 200) {
                return null;
            }
            /** @var array<string, mixed> $data */
            $data = $response->toArray(false);
        } catch (\Throwable) {
            return null;
        }

        $member = $data['response']['GeoObjectCollection']['featureMember'][0]['GeoObject'] ?? null;
        if (!is_array($member)) {
            return null;
        }
        $pos = $member['Point']['pos'] ?? null;
        if (!is_string($pos)) {
            return null;
        }
        $parts = preg_split('/\s+/', trim($pos));
        if ($parts === false || count($parts) < 2) {
            return null;
        }
        $meta = $member['metaDataProperty']['GeocoderMetaData'] ?? [];
        $text = is_array($meta) ? (string) ($meta['text'] ?? '') : '';
        $name = (string) ($member['name'] ?? '');

        return [
            'lat' => (float) $parts[1],
            'lng' => (float) $parts[0],
            'address' => $text !== '' ? $text : $name,
            'name' => $name,
        ];
    }

    /**
     * @param array{lat: float, lng: float, address: string, name: string}|null $nameGeo
     * @param array{lat: float, lng: float, address: string, name: string}|null $addressGeo
     *
     * @return array{lat: float, lng: float, address: string, name: string, source: string}|null
     */
    private function chooseBest(?array $nameGeo, ?array $addressGeo, ?float $lat, ?float $lng, string $city): ?array
    {
        if ($this->isWeakToponym($nameGeo, $city)) {
            $nameGeo = null;
        }
        if ($this->isWeakToponym($addressGeo, $city) && $addressGeo !== null) {
            // Keep street-level addresses even if Yandex collapses them to city in `name`.
            if (!$this->looksLikeStreetAddress($addressGeo['address'])) {
                $addressGeo = null;
            }
        }

        if ($nameGeo === null && $addressGeo === null) {
            return null;
        }

        if ($nameGeo !== null && $addressGeo === null) {
            return $nameGeo + ['source' => 'name'];
        }
        if ($nameGeo === null && $addressGeo !== null) {
            return $addressGeo + ['source' => 'address'];
        }

        /** @var array{lat: float, lng: float, address: string, name: string} $nameGeo */
        /** @var array{lat: float, lng: float, address: string, name: string} $addressGeo */

        $nameDist = ($lat !== null && $lng !== null)
            ? self::haversineM($lat, $lng, $nameGeo['lat'], $nameGeo['lng'])
            : null;
        $addrDist = ($lat !== null && $lng !== null)
            ? self::haversineM($lat, $lng, $addressGeo['lat'], $addressGeo['lng'])
            : null;

        $addressHasHouse = $this->looksLikeStreetAddress($addressGeo['address']);
        $nameHasHouse = $this->looksLikeStreetAddress($nameGeo['address']);

        // Prefer precise address (with house number) over fuzzy toponym.
        if ($addressHasHouse && !$nameHasHouse) {
            return $addressGeo + ['source' => 'address'];
        }

        // Prefer toponym. Switch to address when name result is clearly off
        // while address geocode stays near our current point.
        if ($nameDist !== null && $addrDist !== null && $nameDist > 800 && $addrDist < 400) {
            return $addressGeo + ['source' => 'address'];
        }

        return $nameGeo + ['source' => 'name'];
    }

    /**
     * @param array{lat: float, lng: float, address: string, name: string}|null $geo
     */
    private function isWeakToponym(?array $geo, string $city): bool
    {
        if ($geo === null) {
            return true;
        }

        $name = mb_strtolower(trim($geo['name']));
        $cityLower = mb_strtolower(trim($city));
        if ($name === $cityLower || $name === $cityLower . 'а' || $name === '') {
            return true;
        }

        // "Беларусь, Гомель" / city-only text
        $address = mb_strtolower(trim($geo['address']));
        $parts = array_values(array_filter(array_map('trim', explode(',', $address))));
        if (count($parts) <= 2 && str_contains($address, $cityLower)) {
            return true;
        }

        return false;
    }

    private function looksLikeStreetAddress(string $address): bool
    {
        return preg_match('/\d/u', $address) === 1
            && preg_match('/(улиц|проспект|переулок|площад|проезд|набереж|ул\.|пр\.|пл\.)/iu', $address) === 1;
    }

    private function addressLooksWeak(string $address): bool
    {
        $a = mb_strtolower($address);

        return str_contains($a, 'центр города')
            || str_contains($a, 'берег озера')
            || preg_match('/^(наб\.|пл\.|ул\.)\s+\S+,\s*\S+$/u', $address) === 1;
    }

    private function shortenYandexAddress(string $full, string $city): string
    {
        // Yandex returns "Беларусь, Минск, проспект Победителей, 111". Keep city-local part.
        $parts = array_map('trim', explode(',', $full));
        $parts = array_values(array_filter($parts, static fn (string $p): bool => $p !== ''));
        if ($parts === []) {
            return $full;
        }

        // Drop country.
        if (isset($parts[0]) && preg_match('/беларус/iu', $parts[0])) {
            array_shift($parts);
        }

        // Drop oblast if present.
        if (isset($parts[0]) && preg_match('/область/iu', $parts[0])) {
            array_shift($parts);
        }

        // If first remaining is city, move it to the end in "street, city" form.
        if ($parts !== [] && mb_strtolower($parts[0]) === mb_strtolower($city)) {
            array_shift($parts);
            $street = implode(', ', $parts);

            return $street !== '' ? $street . ', ' . $city : $city;
        }

        $joined = implode(', ', $parts);
        if (!str_contains(mb_strtolower($joined), mb_strtolower($city))) {
            $joined .= ', ' . $city;
        }

        return $joined;
    }

    private static function haversineM(float $lat1, float $lon1, float $lat2, float $lon2): float
    {
        $r = 6371000.0;
        $p1 = deg2rad($lat1);
        $p2 = deg2rad($lat2);
        $dp = deg2rad($lat2 - $lat1);
        $dl = deg2rad($lon2 - $lon1);
        $a = sin($dp / 2) ** 2 + cos($p1) * cos($p2) * sin($dl / 2) ** 2;

        return 2 * $r * asin(min(1.0, sqrt($a)));
    }
}
