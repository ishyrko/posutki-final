<?php

declare(strict_types=1);

namespace App\Application\Command\Article\CreateArticle;

readonly class CreateArticleCommand
{
    public function __construct(
        public string $authorId,
        public string $title,
        public string $content,
        public string $excerpt,
        public ?string $coverImage = null,
        public ?int $categoryId = null,
        public array $tags = [],
    ) {
    }
}