<?php

declare(strict_types=1);

namespace App\Tests\Unit\Application\Service;

use App\Application\Service\LocationDistanceResolver;
use App\Domain\Property\Entity\City;
use PHPUnit\Framework\TestCase;

final class LocationDistanceResolverTest extends TestCase
{
    public function testHidesDistancesBelowVisibleThreshold(): void
    {
        $city = $this->createCity(10, 'minsk', 'Минск', 'Минска');

        $result = LocationDistanceResolver::resolve(
            10,
            0.2,
            $city,
            10,
            0.2,
            $city,
        );

        self::assertNull($result['nearestCity']);
        self::assertNull($result['regionCenter']);
    }

    public function testDeduplicatesRegionCenterWhenSameAsNearestCity(): void
    {
        $city = $this->createCity(10, 'brest', 'Брест', 'Бреста');

        $result = LocationDistanceResolver::resolve(
            10,
            12.4,
            $city,
            10,
            150.0,
            $city,
        );

        self::assertNotNull($result['nearestCity']);
        self::assertNull($result['regionCenter']);
    }

    public function testRoundsDisplayDistance(): void
    {
        $city = $this->createCity(11, 'lida', 'Лида', 'Лиды');

        $result = LocationDistanceResolver::resolve(
            11,
            9.44,
            $city,
            null,
            null,
            null,
        );

        self::assertSame(9.4, $result['nearestCity']['distanceKm']);
    }

    private function createCity(int $id, string $slug, string $name, string $genitive): City
    {
        $city = new City();
        $reflection = new \ReflectionClass($city);

        $reflection->getProperty('id')->setValue($city, $id);
        $reflection->getProperty('name')->setValue($city, $name);
        $reflection->getProperty('slug')->setValue($city, $slug);
        $reflection->getProperty('shortName')->setValue($city, $name);
        $reflection->getProperty('nameGenitive')->setValue($city, $genitive);
        $reflection->getProperty('isCity')->setValue($city, true);

        return $city;
    }
}
