<?php

declare(strict_types=1);

namespace App\Domain\Property\Service;

/**
 * Point-to-polygon distance on a local equirectangular projection (km).
 *
 * @param list<list<array{0: float, 1: float}>> $rings Each ring is [[lat, lon], ...]
 */
final class PolygonDistance
{
    private const EARTH_RADIUS_KM = 6371.0;

    public static function distanceKm(float $latitude, float $longitude, array $rings): float
    {
        if ($rings === []) {
            return INF;
        }

        $lat0 = deg2rad($latitude);
        $lon0 = deg2rad($longitude);
        $cosLat0 = cos($lat0);

        $px = 0.0;
        $py = 0.0;

        $projectedRings = [];
        foreach ($rings as $ring) {
            $projected = [];
            foreach ($ring as $point) {
                $lat = deg2rad((float) $point[0]);
                $lon = deg2rad((float) $point[1]);
                $x = ($lon - $lon0) * $cosLat0 * self::EARTH_RADIUS_KM;
                $y = ($lat - $lat0) * self::EARTH_RADIUS_KM;
                $projected[] = [$x, $y];
            }
            $projectedRings[] = $projected;
        }

        foreach ($projectedRings as $ring) {
            if (self::pointInRing($px, $py, $ring)) {
                return 0.0;
            }
        }

        $minDistance = INF;
        foreach ($projectedRings as $ring) {
            $count = count($ring);
            if ($count < 2) {
                continue;
            }

            for ($i = 0; $i < $count; ++$i) {
                $j = ($i + 1) % $count;
                $distance = self::pointToSegmentDistance(
                    $px,
                    $py,
                    $ring[$i][0],
                    $ring[$i][1],
                    $ring[$j][0],
                    $ring[$j][1],
                );
                if ($distance < $minDistance) {
                    $minDistance = $distance;
                }
            }
        }

        return $minDistance;
    }

    /**
     * @param list<array{0: float, 1: float}> $ring
     */
    private static function pointInRing(float $x, float $y, array $ring): bool
    {
        $inside = false;
        $count = count($ring);

        for ($i = 0, $j = $count - 1; $i < $count; $j = $i++) {
            $xi = $ring[$i][0];
            $yi = $ring[$i][1];
            $xj = $ring[$j][0];
            $yj = $ring[$j][1];

            $intersects = ($yi > $y) !== ($yj > $y)
                && $x < (($xj - $xi) * ($y - $yi) / (($yj - $yi) ?: 1e-12) + $xi);

            if ($intersects) {
                $inside = !$inside;
            }
        }

        return $inside;
    }

    private static function pointToSegmentDistance(
        float $px,
        float $py,
        float $x1,
        float $y1,
        float $x2,
        float $y2,
    ): float {
        $dx = $x2 - $x1;
        $dy = $y2 - $y1;
        $lengthSquared = $dx * $dx + $dy * $dy;

        if ($lengthSquared === 0.0) {
            return hypot($px - $x1, $py - $y1);
        }

        $t = max(0.0, min(1.0, (($px - $x1) * $dx + ($py - $y1) * $dy) / $lengthSquared));

        return hypot($px - ($x1 + $t * $dx), $py - ($y1 + $t * $dy));
    }
}
