<?php

declare(strict_types=1);

namespace App\Tests\Unit\Infrastructure\Migration\Data;

use App\Infrastructure\Migration\Data\ArendomPartnerSplitData;
use PHPUnit\Framework\TestCase;

final class ArendomPartnerSplitDataTest extends TestCase
{
    public function testCityListsDoNotOverlapAndAreUnique(): void
    {
        $first = ArendomPartnerSplitData::FIRST_PARTNER_CITY_SLUGS;
        $second = ArendomPartnerSplitData::SECOND_PARTNER_CITY_SLUGS;

        self::assertSame($first, array_values(array_unique($first)));
        self::assertSame($second, array_values(array_unique($second)));
        self::assertSame([], array_values(array_intersect($first, $second)));
        self::assertCount(55, $first);
        self::assertCount(33, $second);
    }

    public function testSecondPartnerLookupMatchesTheOlgaDianaCities(): void
    {
        self::assertTrue(ArendomPartnerSplitData::isSecondPartnerCity('soligorsk'));
        self::assertTrue(ArendomPartnerSplitData::isSecondPartnerCity('druzhnyy-p'));
        self::assertFalse(ArendomPartnerSplitData::isSecondPartnerCity('minsk'));
        self::assertFalse(ArendomPartnerSplitData::isSecondPartnerCity('uzda-g'));
    }
}
