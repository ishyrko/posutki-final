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

    private function city(string $name): City
    {
        $city = new City();
        $city->setName($name);

        return $city;
    }
}
