<?php

declare(strict_types=1);

namespace App\Tests\Application\Property;

use App\Application\Service\PropertyEngagementStatsCache;
use App\Domain\Property\Repository\PropertyEngagementStatsRepositoryInterface;
use PHPUnit\Framework\TestCase;

final class PropertyEngagementStatsCacheTest extends TestCase
{
    public function testWarmUpLoadsMissingPropertyStats(): void
    {
        $repository = $this->createMock(PropertyEngagementStatsRepositoryInterface::class);
        $repository
            ->expects(self::once())
            ->method('findCountsByPropertyIds')
            ->with([10, 20])
            ->willReturn([
                10 => ['distinctInquirers' => 3, 'distinctMessageSenders' => 2],
            ]);

        $cache = new PropertyEngagementStatsCache($repository);
        $cache->warmUp([10, 20]);

        self::assertSame(3, $cache->getDistinctInquirers(10));
        self::assertSame(2, $cache->getDistinctMessageSenders(10));
        self::assertSame(0, $cache->getDistinctInquirers(20));
        self::assertSame(0, $cache->getDistinctMessageSenders(20));
    }

    public function testWarmUpDoesNotReloadAlreadyCachedProperty(): void
    {
        $repository = $this->createMock(PropertyEngagementStatsRepositoryInterface::class);
        $repository
            ->expects(self::once())
            ->method('findCountsByPropertyIds')
            ->with([10])
            ->willReturn([
                10 => ['distinctInquirers' => 1, 'distinctMessageSenders' => 4],
            ]);

        $cache = new PropertyEngagementStatsCache($repository);
        $cache->warmUp([10]);
        $cache->warmUp([10, 20]);

        self::assertSame(1, $cache->getDistinctInquirers(10));
        self::assertSame(4, $cache->getDistinctMessageSenders(10));
        self::assertSame(0, $cache->getDistinctInquirers(20));
    }
}
