<?php

declare(strict_types=1);

namespace App\Tests\Unit\Domain\Property\Service;

use App\Domain\Property\Service\PolygonDistance;
use PHPUnit\Framework\TestCase;

final class PolygonDistanceTest extends TestCase
{
    public function testPointInsidePolygonReturnsZero(): void
    {
        $rings = [[
            [53.0, 27.0],
            [53.0, 27.1],
            [53.1, 27.1],
            [53.1, 27.0],
            [53.0, 27.0],
        ]];

        self::assertSame(0.0, PolygonDistance::distanceKm(53.05, 27.05, $rings));
    }

    public function testPointOutsideReturnsDistanceToNearestEdge(): void
    {
        $rings = [[
            [53.0, 27.0],
            [53.0, 27.1],
            [53.1, 27.1],
            [53.1, 27.0],
            [53.0, 27.0],
        ]];

        $distance = PolygonDistance::distanceKm(52.99, 27.05, $rings);

        self::assertGreaterThan(0.5, $distance);
        self::assertLessThan(2.0, $distance);
    }

    public function testEmptyRingsReturnInfinity(): void
    {
        self::assertSame(INF, PolygonDistance::distanceKm(53.05, 27.05, []));
    }
}
