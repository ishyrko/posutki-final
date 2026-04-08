<?php

declare(strict_types=1);

namespace App\Application\DTO;

use App\Domain\StaticPage\Entity\StaticPage;

final class StaticPageDTO
{
    public function __construct(
        public readonly int $id,
        public readonly string $slug,
        public readonly string $title,
        public readonly string $content,
        public readonly ?string $metaTitle,
        public readonly ?string $metaDescription,
        public readonly string $updatedAt,
    ) {
    }

    public static function fromEntity(StaticPage $page): self
    {
        return new self(
            id: $page->getId()->getValue(),
            slug: $page->getSlug()->getValue(),
            title: $page->getTitle(),
            content: $page->getContent(),
            metaTitle: $page->getMetaTitle(),
            metaDescription: $page->getMetaDescription(),
            updatedAt: $page->getUpdatedAt()->format('Y-m-d H:i:s'),
        );
    }
}
