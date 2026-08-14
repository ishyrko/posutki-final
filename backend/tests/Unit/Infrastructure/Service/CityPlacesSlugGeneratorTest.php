<?php

declare(strict_types=1);

namespace App\Tests\Unit\Infrastructure\Service;

use App\Domain\Property\Entity\CityMicrodistrict;
use App\Domain\Property\Entity\ResidentialComplex;
use App\Domain\Property\Repository\CityMicrodistrictRepositoryInterface;
use App\Domain\Property\Repository\ResidentialComplexRepositoryInterface;
use App\Infrastructure\Service\CityMicrodistrictSlugGenerator;
use App\Infrastructure\Service\ResidentialComplexSlugGenerator;
use App\Infrastructure\Service\SlugGenerator;
use PHPUnit\Framework\TestCase;

final class CityPlacesSlugGeneratorTest extends TestCase
{
    public function testMicrodistrictSlugStripsPrefix(): void
    {
        $repository = $this->createMock(CityMicrodistrictRepositoryInterface::class);
        $repository->method('findByCityIdAndSlug')->willReturn(null);

        $generator = new CityMicrodistrictSlugGenerator(new SlugGenerator(), $repository);

        self::assertSame('uruche', $generator->generateSlug(1, 'микрорайон Уручье'));
    }

    public function testMicrodistrictSlugEnsuresUniquenessWithinCity(): void
    {
        $existing = new CityMicrodistrict(1, 'микрорайон Уручье', 'Уручье', 'Уручье', 'uruche');

        $repository = $this->createMock(CityMicrodistrictRepositoryInterface::class);
        $repository
            ->method('findByCityIdAndSlug')
            ->willReturnCallback(static fn (int $cityId, string $slug): ?CityMicrodistrict => $slug === 'uruche' ? $existing : null);

        $generator = new CityMicrodistrictSlugGenerator(new SlugGenerator(), $repository);

        self::assertSame('uruche-1', $generator->generateSlug(1, 'микрорайон Уручье'));
    }

    public function testResidentialComplexSlugStripsPrefix(): void
    {
        $repository = $this->createMock(ResidentialComplexRepositoryInterface::class);
        $repository->method('findByCityIdAndSlug')->willReturn(null);

        $generator = new ResidentialComplexSlugGenerator(new SlugGenerator(), $repository);

        self::assertSame('minsk-mir', $generator->generateSlug(1, 'экспериментальный многофункциональный комплекс Минск-Мир'));
    }

    public function testResidentialComplexSlugEnsuresUniquenessWithinCity(): void
    {
        $existing = new ResidentialComplex(1, 'ЖК Маяк Минска', 'Маяк Минска', 'Маяке Минска', 'mayak-minska');

        $repository = $this->createMock(ResidentialComplexRepositoryInterface::class);
        $repository
            ->method('findByCityIdAndSlug')
            ->willReturnCallback(static fn (int $cityId, string $slug): ?ResidentialComplex => $slug === 'mayak-minska' ? $existing : null);

        $generator = new ResidentialComplexSlugGenerator(new SlugGenerator(), $repository);

        self::assertSame('mayak-minska-1', $generator->generateSlug(1, 'ЖК Маяк Минска'));
    }
}
