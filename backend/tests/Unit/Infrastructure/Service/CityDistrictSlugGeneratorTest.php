<?php

declare(strict_types=1);

namespace App\Tests\Unit\Infrastructure\Service;

use App\Domain\Property\Repository\CityDistrictRepositoryInterface;
use App\Infrastructure\Service\CityDistrictSlugGenerator;
use App\Infrastructure\Service\SlugGenerator;
use PHPUnit\Framework\TestCase;

final class CityDistrictSlugGeneratorTest extends TestCase
{
    public function testStripDistrictSuffixRemovesRayonWordForms(): void
    {
        self::assertSame('Центральный', CityDistrictSlugGenerator::stripDistrictSuffix('Центральный район'));
        self::assertSame('Советский', CityDistrictSlugGenerator::stripDistrictSuffix('Советский района'));
        self::assertSame('Ленинский', CityDistrictSlugGenerator::stripDistrictSuffix('Ленинский районе'));
        self::assertSame('Октябрьский', CityDistrictSlugGenerator::stripDistrictSuffix('Октябрьский районом'));
    }

    public function testGenerateSlugStripsRayonBeforeTransliteration(): void
    {
        $repository = $this->createMock(CityDistrictRepositoryInterface::class);
        $repository->method('findByCityIdAndSlug')->willReturn(null);

        $generator = new CityDistrictSlugGenerator(new SlugGenerator(), $repository);

        self::assertSame('tsentralnyy', $generator->generateSlug(1, 'Центральный район'));
    }

    public function testGenerateSlugEnsuresUniquenessWithinCity(): void
    {
        $existing = new \App\Domain\Property\Entity\CityDistrict(1, 'Советский район', 'sovetskiy');

        $repository = $this->createMock(CityDistrictRepositoryInterface::class);
        $repository
            ->method('findByCityIdAndSlug')
            ->willReturnCallback(static fn (int $cityId, string $slug): ?\App\Domain\Property\Entity\CityDistrict => $slug === 'sovetskiy' ? $existing : null);

        $generator = new CityDistrictSlugGenerator(new SlugGenerator(), $repository);

        self::assertSame('sovetskiy-1', $generator->generateSlug(1, 'Советский район'));
    }
}
