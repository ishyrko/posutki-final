<?php

declare(strict_types=1);

namespace App\Tests\Unit\Domain\Property\Entity;

use App\Domain\Property\Entity\City;
use PHPUnit\Framework\TestCase;
use Symfony\Component\PropertyAccess\PropertyAccess;

final class CityTest extends TestCase
{
    public function testApartmentCatalogFlagIsWritableViaPropertyAccess(): void
    {
        $city = new City();
        $accessor = PropertyAccess::createPropertyAccessor();

        $accessor->setValue($city, 'isApartmentCatalog', true);

        self::assertTrue($city->isApartmentCatalog());
        self::assertTrue($accessor->getValue($city, 'isApartmentCatalog'));
    }

    public function testListingSuggestedFlagIsWritableViaPropertyAccess(): void
    {
        $city = new City();
        $accessor = PropertyAccess::createPropertyAccessor();

        $accessor->setValue($city, 'isListingSuggested', true);

        self::assertTrue($city->isListingSuggested());
        self::assertTrue($accessor->getValue($city, 'isListingSuggested'));
    }
}
