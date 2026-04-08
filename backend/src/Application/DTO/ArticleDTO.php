<?php

declare(strict_types=1);

namespace App\Application\DTO;

use App\Domain\Article\Entity\Article;

final class ArticleDTO
{
    public function __construct(
        public readonly int $id,
        public readonly int $authorId,
        public readonly string $title,
        public readonly string $slug,
        public readonly string $content,
        public readonly string $excerpt,
        public readonly ?string $coverImage,
        public readonly ?int $categoryId,
        public readonly ?string $categoryName,
        public readonly ?string $categorySlug,
        public readonly array $tags,
        public readonly string $status,
        public readonly int $views,
        public readonly string $createdAt,
        public readonly string $updatedAt,
        public readonly ?string $publishedAt = null,
    ) {
    }

    public static function fromEntity(Article $article): self
    {
        return new self(
            id: $article->getId()->getValue(),
            authorId: $article->getAuthorId()->getValue(),
            title: $article->getTitle(),
            slug: $article->getSlug()->getValue(),
            content: $article->getContent(),
            excerpt: $article->getExcerpt(),
            coverImage: $article->getCoverImage(),
            categoryId: $article->getCategory()?->getId(),
            categoryName: $article->getCategory()?->getName(),
            categorySlug: $article->getCategory()?->getSlug(),
            tags: $article->getTags(),
            status: $article->getStatus(),
            views: $article->getViews(),
            createdAt: $article->getCreatedAt()->format('Y-m-d H:i:s'),
            updatedAt: $article->getUpdatedAt()->format('Y-m-d H:i:s'),
            publishedAt: $article->getPublishedAt()?->format('Y-m-d H:i:s'),
        );
    }
}