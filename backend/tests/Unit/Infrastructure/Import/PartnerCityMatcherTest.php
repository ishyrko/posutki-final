<?php

declare(strict_types=1);

namespace App\Tests\Unit\Infrastructure\Import;

use App\Domain\Property\Entity\City;
use App\Infrastructure\Import\PartnerCityMatcher;
use PHPUnit\Framework\TestCase;

final class PartnerCityMatcherTest extends TestCase
{
    public function testPrefersTownOverSubstringVillage(): void
    {
        $village = $this->city('Великий Каменец д.');
        $town = $this->city('Каменец г.');
        $hamlet = $this->city('Каменец д.');

        $picked = PartnerCityMatcher::pick('Каменец', [$village, $town, $hamlet]);

        self::assertSame($town, $picked);
    }

    public function testDoesNotFallBackToSubstringMatch(): void
    {
        $village = $this->city('Великий Каменец д.');
        $other = $this->city('Малый Каменец д.');

        self::assertNull(PartnerCityMatcher::pick('Каменец', [$village, $other]));
    }

    public function testMatchesYoFoldedNames(): void
    {
        $mogilev = $this->city('Могилёв г.');

        self::assertSame($mogilev, PartnerCityMatcher::pick('Могилев', [$mogilev]));
    }

    public function testPrefersUnsuffixedCityOverAgrotown(): void
    {
        $agrotown = $this->city('Гомель аг.');
        $city = $this->city('Гомель');
        $village = $this->city('Речица д.');
        $rechitsa = $this->city('Речица');
        $rechitsaAg = $this->city('Речица аг.');
        $staryeDorogi = $this->city('Старые Дороги');
        $staryeDorogiAg = $this->city('Старые Дороги аг.');

        self::assertSame($city, PartnerCityMatcher::pick('Гомель', [$agrotown, $city]));
        self::assertSame($rechitsa, PartnerCityMatcher::pick('Речица', [$village, $rechitsaAg, $rechitsa]));
        self::assertSame($staryeDorogi, PartnerCityMatcher::pick('Старые Дороги', [$staryeDorogiAg, $staryeDorogi]));
    }

    public function testMatchesUrbanSettlementWithDottedGpSuffix(): void
    {
        $urban = $this->city('Мачулищи г.п.');
        $village = $this->city('Мачулище д.');

        self::assertSame($urban, PartnerCityMatcher::pick('Мачулищи', [$village, $urban]));
        self::assertSame('мачулищи', PartnerCityMatcher::coreName('Мачулищи г.п.'));
    }

    private function city(string $name): City
    {
        $city = new City();
        $city->setName($name);

        return $city;
    }
}
