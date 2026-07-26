<?php

declare(strict_types=1);

namespace App\Application\Service;

use App\Domain\Analytics\Repository\ContentViewDedupRepositoryInterface;
use App\Domain\Article\Entity\Article;
use App\Domain\Article\Repository\ArticleRepositoryInterface;
use App\Domain\Property\Entity\Property;
use App\Domain\Property\Repository\PropertyDailyStatRepositoryInterface;
use App\Domain\Property\Repository\PropertyRepositoryInterface;

final readonly class ContentViewTracker
{
    public const ENTITY_PROPERTY = 'property';
    public const ENTITY_ARTICLE = 'article';

    public function __construct(
        private ContentViewDedupRepositoryInterface $contentViewDedupRepository,
        private PropertyRepositoryInterface $propertyRepository,
        private PropertyDailyStatRepositoryInterface $propertyDailyStatRepository,
        private ArticleRepositoryInterface $articleRepository,
    ) {
    }

    /**
     * @return array{views: int, counted: bool}
     */
    public function trackProperty(Property $property, ?string $viewerUserId, ?string $visitorId, ?string $userAgent): array
    {
        if ($this->shouldSkipTracking(
            $viewerUserId,
            $viewerUserId !== null && $property->isOwnedBy($viewerUserId),
            $userAgent,
        )) {
            return ['views' => $property->getViews(), 'counted' => false];
        }

        $visitorKey = $this->resolveVisitorKey($viewerUserId, $visitorId);
        if ($visitorKey === null) {
            return ['views' => $property->getViews(), 'counted' => false];
        }

        if (!$this->contentViewDedupRepository->tryAcquireUniqueView(
            self::ENTITY_PROPERTY,
            (string) $property->getId()->getValue(),
            $visitorKey,
            new \DateTimeImmutable('today'),
        )) {
            return ['views' => $property->getViews(), 'counted' => false];
        }

        $property->incrementViews();
        $this->propertyRepository->save($property);
        $this->propertyDailyStatRepository->upsertView($property->getId()->getValue(), new \DateTimeImmutable());

        return ['views' => $property->getViews(), 'counted' => true];
    }

    /**
     * @return array{views: int, counted: bool}
     */
    public function trackArticle(Article $article, ?string $viewerUserId, ?string $visitorId, ?string $userAgent): array
    {
        if ($this->shouldSkipTracking(
            $viewerUserId,
            $viewerUserId !== null && $article->isAuthoredBy($viewerUserId),
            $userAgent,
        )) {
            return ['views' => $article->getViews(), 'counted' => false];
        }

        $visitorKey = $this->resolveVisitorKey($viewerUserId, $visitorId);
        if ($visitorKey === null) {
            return ['views' => $article->getViews(), 'counted' => false];
        }

        if (!$this->contentViewDedupRepository->tryAcquireUniqueView(
            self::ENTITY_ARTICLE,
            (string) $article->getId()->getValue(),
            $visitorKey,
            new \DateTimeImmutable('today'),
        )) {
            return ['views' => $article->getViews(), 'counted' => false];
        }

        $article->incrementViews();
        $this->articleRepository->save($article);

        return ['views' => $article->getViews(), 'counted' => true];
    }

    private function shouldSkipTracking(?string $viewerUserId, bool $isOwner, ?string $userAgent): bool
    {
        if ($isOwner) {
            return true;
        }

        return $this->isBot($userAgent);
    }

    private function resolveVisitorKey(?string $viewerUserId, ?string $visitorId): ?string
    {
        if ($viewerUserId !== null && $viewerUserId !== '') {
            return 'user:' . $viewerUserId;
        }

        $visitorId = trim((string) $visitorId);
        if ($visitorId === '' || strlen($visitorId) > 64 || !preg_match('/^[a-zA-Z0-9_-]+$/', $visitorId)) {
            return null;
        }

        return 'anon:' . $visitorId;
    }

    private function isBot(?string $userAgent): bool
    {
        if ($userAgent === null || $userAgent === '') {
            return false;
        }

        return (bool) preg_match(
            '/bot|crawl|spider|slurp|facebookexternalhit|preview|headless|wget|curl|python-requests/i',
            $userAgent,
        );
    }
}
