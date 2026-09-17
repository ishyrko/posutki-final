<?php

declare(strict_types=1);

namespace App\Tests\Unit\Infrastructure\Import;

use App\Infrastructure\Import\PartnerBathroomMapper;
use PHPUnit\Framework\TestCase;

final class PartnerBathroomMapperTest extends TestCase
{
    public function testSeparateTypeBecomesOneBathroomWithAmenity(): void
    {
        $result = (new PartnerBathroomMapper())->apply(
            ['bathroomType' => 'separate', 'bathrooms' => 2],
            ['wifi'],
        );

        self::assertSame(1, $result['bathrooms']);
        self::assertSame(['wifi', 'bathroom_separate'], $result['amenities']);
    }

    public function testCombinedTypeBecomesOneBathroomWithAmenity(): void
    {
        $result = (new PartnerBathroomMapper())->apply(
            ['bathroomType' => 'combined'],
            [],
        );

        self::assertSame(1, $result['bathrooms']);
        self::assertSame(['bathroom_combined'], $result['amenities']);
    }

    public function testTwoTypeKeepsTwoBathrooms(): void
    {
        $result = (new PartnerBathroomMapper())->apply(
            ['bathroomType' => 'two', 'bathrooms' => 2],
            ['bathroom_separate', 'wifi'],
        );

        self::assertSame(2, $result['bathrooms']);
        self::assertSame(['wifi'], $result['amenities']);
    }

    public function testAmenitySeparateOverridesLegacyBathroomCount(): void
    {
        $result = (new PartnerBathroomMapper())->apply(
            ['bathrooms' => 2],
            ['bathroom_separate', 'fridge'],
        );

        self::assertSame(1, $result['bathrooms']);
        self::assertSame(['fridge', 'bathroom_separate'], $result['amenities']);
    }

    public function testLegacyBathroomCountTwoMeansSeparate(): void
    {
        $result = (new PartnerBathroomMapper())->apply(
            ['bathrooms' => 2],
            ['wifi'],
        );

        self::assertSame(1, $result['bathrooms']);
        self::assertSame(['wifi', 'bathroom_separate'], $result['amenities']);
    }

    public function testMissingBathroomDefaultsToCombined(): void
    {
        $result = (new PartnerBathroomMapper())->apply([], ['tv']);

        self::assertSame(1, $result['bathrooms']);
        self::assertSame(['tv', 'bathroom_combined'], $result['amenities']);
    }
}
