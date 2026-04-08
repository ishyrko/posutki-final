<?php

declare(strict_types=1);

namespace App\Application\Query\Article\GetArticle;

readonly class GetArticleQuery
{
    public function __construct(
        public ?string $id = null,
        public ?string $slug = null,
    ) {
    }
}