<?php

declare(strict_types=1);

namespace App\Application\Command\Article\UpdateArticle;

readonly class UpdateArticleCommand
{
    public function __construct(
        public string $articleId,
        public string $userId,
        public ?string $title = null,
        public ?string $content = null,
        public ?string $excerpt = null,
        public ?string $coverImage = null,
        public ?int $categoryId = null,
        public ?array $tags = null,
    ) {
    }
}