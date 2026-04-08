<?php

declare(strict_types=1);

namespace App\Tests\Unit\Domain\Property\ValueObject;

use App\Domain\Property\ValueObject\Coordinates;
use PHPUnit\Framework\TestCase;

final class CoordinatesTest extends TestCase
{
    public function testDistanceToReturnsZeroForSamePoint(): void
    {
        $point = Coordinates::create(53.9045, 27.5615);

        self::assertSame(0.0, $point->distanceTo($point));
    }

    public function testDistanceToReturnsExpectedDistanceForKnownPoints(): void
    {
        $minskCenter = Coordinates::create(53.9045, 27.5615);
        $minskStation = Coordinates::create(53.8930, 27.5547);

        $distance = $minskCenter->distanceTo($minskStation);

        self::assertGreaterThan(1.2, $distance);
        self::assertLessThan(1.6, $distance);
    }
}
