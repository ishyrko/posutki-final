<?php

declare(strict_types=1);

namespace App\Tests\Unit\Application\Service;

use App\Application\Service\CityNameGenitiveResolver;
use App\Domain\Property\Entity\City;
use PHPUnit\Framework\TestCase;

final class CityNameGenitiveResolverTest extends TestCase
{
    private CityNameGenitiveResolver $resolver;

    protected function setUp(): void
    {
        $this->resolver = new CityNameGenitiveResolver();
    }

    public function testUsesSeedOverrideForKnownCity(): void
    {
        $city = $this->createCity('myadel-g', 'Мядель г.', null);

        self::assertSame('Мяделя', $this->resolver->resolve($city));
    }

    public function testDeclinesCityNameWithoutAdministrativeSuffix(): void
    {
        $city = $this->createCity('minsk', 'Минск', null);

        self::assertSame('Минска', $this->resolver->resolve($city));
    }

    public function testDeclinesPluralCityName(): void
    {
        $city = $this->createCity('unknown', 'Барановичи', null);

        self::assertSame('Барановичей', $this->resolver->resolve($city));
    }

    public function testKeepsIndeclinableCityName(): void
    {
        $city = $this->createCity('unknown', 'Жодино', null);

        self::assertSame('Жодино', $this->resolver->resolve($city));
    }

    public function testDeclinesCompoundCityName(): void
    {
        self::assertSame(
            'Марьиной Горки',
            $this->resolver->resolveFromName('Марьина Горка г.', 'marina-gorka-g'),
        );
    }

    public function testPrefersManualValueForUnknownCity(): void
    {
        $city = $this->createCity('custom-city', 'Новый Город', 'Нового Города');

        self::assertSame('Нового Города', $this->resolver->resolve($city));
    }

    private function createCity(string $slug, string $name, ?string $genitive): City
    {
        $city = new City();
        $reflection = new \ReflectionClass($city);

        $reflection->getProperty('slug')->setValue($city, $slug);
        $reflection->getProperty('name')->setValue($city, $name);
        $reflection->getProperty('shortName')->setValue($city, $name);
        $reflection->getProperty('nameGenitive')->setValue($city, $genitive);

        return $city;
    }
}
