<?php

declare(strict_types=1);

namespace App\Tests\Unit\Infrastructure\Import;

use App\Infrastructure\Import\PartnerAmenityMapper;
use PHPUnit\Framework\TestCase;

final class PartnerAmenityMapperTest extends TestCase
{
    public function testMapsRussianLabelsAndSynonyms(): void
    {
        $mapper = new PartnerAmenityMapper();

        self::assertSame(
            ['wifi', 'fridge', 'tv', 'washing_machine', 'bathroom_separate', 'bathroom_combined'],
            $mapper->map(['Wi-Fi', 'холодильник', 'Телевизор', 'стиралка', 'раздельный', 'совмещенный']),
        );
    }
}
