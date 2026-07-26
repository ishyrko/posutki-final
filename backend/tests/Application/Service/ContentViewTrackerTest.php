<?php

declare(strict_types=1);

namespace App\Tests\Application\Service;

use App\Application\Service\ContentViewTracker;
use App\Domain\Analytics\Repository\ContentViewDedupRepositoryInterface;
use App\Domain\Article\Entity\Article;
use App\Domain\Article\Repository\ArticleRepositoryInterface;
use App\Domain\Property\Entity\Property;
use App\Domain\Property\Repository\PropertyDailyStatRepositoryInterface;
use App\Domain\Property\Repository\PropertyRepositoryInterface;
use App\Domain\Property\ValueObject\Address;
use App\Domain\Property\ValueObject\Coordinates;
use App\Domain\Property\ValueObject\Price;
use App\Domain\Shared\ValueObject\Id;
use App\Domain\Shared\ValueObject\Slug;
use PHPUnit\Framework\TestCase;

final class ContentViewTrackerTest extends TestCase
{
    public function testPropertyViewIsCountedOncePerVisitorPerDay(): void
    {
        $property = $this->createProperty(ownerId: 4);
        $dedup = $this->createMock(ContentViewDedupRepositoryInterface::class);
        $dedup->expects(self::once())->method('tryAcquireUniqueView')->willReturn(true);

        $propertyRepository = $this->createMock(PropertyRepositoryInterface::class);
        $propertyRepository->expects(self::once())->method('save')->with($property);

        $dailyStats = $this->createMock(PropertyDailyStatRepositoryInterface::class);
        $dailyStats->expects(self::once())->method('upsertView');

        $tracker = new ContentViewTracker(
            $dedup,
            $propertyRepository,
            $dailyStats,
            $this->createStub(ArticleRepositoryInterface::class),
        );

        $result = $tracker->trackProperty($property, null, 'visitor-abc', 'Mozilla/5.0');

        self::assertTrue($result['counted']);
        self::assertSame(1, $result['views']);
    }

    public function testDuplicatePropertyViewIsIgnored(): void
    {
        $property = $this->createProperty(ownerId: 4);
        $property->incrementViews();

        $dedup = $this->createMock(ContentViewDedupRepositoryInterface::class);
        $dedup->expects(self::once())->method('tryAcquireUniqueView')->willReturn(false);

        $propertyRepository = $this->createMock(PropertyRepositoryInterface::class);
        $propertyRepository->expects(self::never())->method('save');

        $tracker = new ContentViewTracker(
            $dedup,
            $propertyRepository,
            $this->createStub(PropertyDailyStatRepositoryInterface::class),
            $this->createStub(ArticleRepositoryInterface::class),
        );

        $result = $tracker->trackProperty($property, null, 'visitor-abc', 'Mozilla/5.0');

        self::assertFalse($result['counted']);
        self::assertSame(1, $result['views']);
    }

    public function testOwnerPropertyViewIsNotCounted(): void
    {
        $property = $this->createProperty(ownerId: 4);

        $dedup = $this->createMock(ContentViewDedupRepositoryInterface::class);
        $dedup->expects(self::never())->method('tryAcquireUniqueView');

        $tracker = new ContentViewTracker(
            $dedup,
            $this->createStub(PropertyRepositoryInterface::class),
            $this->createStub(PropertyDailyStatRepositoryInterface::class),
            $this->createStub(ArticleRepositoryInterface::class),
        );

        $result = $tracker->trackProperty($property, '4', 'visitor-abc', 'Mozilla/5.0');

        self::assertFalse($result['counted']);
        self::assertSame(0, $result['views']);
    }

    public function testBotPropertyViewIsNotCounted(): void
    {
        $property = $this->createProperty(ownerId: 4);

        $dedup = $this->createMock(ContentViewDedupRepositoryInterface::class);
        $dedup->expects(self::never())->method('tryAcquireUniqueView');

        $tracker = new ContentViewTracker(
            $dedup,
            $this->createStub(PropertyRepositoryInterface::class),
            $this->createStub(PropertyDailyStatRepositoryInterface::class),
            $this->createStub(ArticleRepositoryInterface::class),
        );

        $result = $tracker->trackProperty($property, null, 'visitor-abc', 'Googlebot/2.1');

        self::assertFalse($result['counted']);
    }

    public function testArticleViewUsesAuthenticatedVisitorKey(): void
    {
        $article = new Article(
            authorId: Id::fromInt(9),
            title: 'Test article',
            slug: Slug::fromString('test-article'),
            content: 'Content long enough for article entity validation in tests.',
            excerpt: 'Excerpt',
            status: 'published',
        );
        $articleIdReflection = new \ReflectionProperty($article, 'id');
        $articleIdReflection->setAccessible(true);
        $articleIdReflection->setValue($article, Id::fromInt(55));

        $dedup = $this->createMock(ContentViewDedupRepositoryInterface::class);
        $dedup->expects(self::once())
            ->method('tryAcquireUniqueView')
            ->with(
                ContentViewTracker::ENTITY_ARTICLE,
                (string) $article->getId()->getValue(),
                'user:7',
                self::isInstanceOf(\DateTimeImmutable::class),
            )
            ->willReturn(true);

        $articleRepository = $this->createMock(ArticleRepositoryInterface::class);
        $articleRepository->expects(self::once())->method('save')->with($article);

        $tracker = new ContentViewTracker(
            $dedup,
            $this->createStub(PropertyRepositoryInterface::class),
            $this->createStub(PropertyDailyStatRepositoryInterface::class),
            $articleRepository,
        );

        $result = $tracker->trackArticle($article, '7', null, 'Mozilla/5.0');

        self::assertTrue($result['counted']);
        self::assertSame(1, $result['views']);
    }

    private function createProperty(int $ownerId): Property
    {
        $property = new Property(
            ownerId: Id::fromInt($ownerId),
            type: 'apartment',
            dealType: 'daily',
            title: 'Content view tracker test listing',
            description: 'Content view tracker test listing description with enough length.',
            price: Price::fromAmount(10000000, 'BYN'),
            area: 60.0,
            rooms: 2,
            floor: 3,
            totalFloors: 9,
            bathrooms: 1,
            yearBuilt: 2015,
            renovation: null,
            balcony: null,
            livingArea: null,
            kitchenArea: null,
            dealConditions: null,
            paymentMethods: null,
            maxDailyGuests: 4,
            dailySingleBeds: null,
            dailyDoubleBeds: null,
            checkInTime: null,
            checkOutTime: null,
            address: Address::create('1', null),
            cityId: 1,
            coordinates: Coordinates::create(53.9, 27.56),
        );

        $idReflection = new \ReflectionProperty($property, 'id');
        $idReflection->setAccessible(true);
        $idReflection->setValue($property, Id::fromInt(20));

        return $property;
    }
}
