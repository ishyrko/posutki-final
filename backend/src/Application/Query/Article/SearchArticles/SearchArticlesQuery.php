<?php

declare(strict_types=1);

namespace App\Application\Query\Article\SearchArticles;

readonly class SearchArticlesQuery
{
    public function __construct(
        public ?string $status = 'published',
        public ?int $categoryId = null,
        public ?string $categorySlug = null,
        public ?string $tag = null,
        public ?string $authorId = null,
        public int $page = 1,
        public int $limit = 20,
    ) {
    }
}