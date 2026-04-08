<?php

declare(strict_types=1);

namespace App\Application\Command\Article\PublishArticle;

readonly class PublishArticleCommand
{
    public function __construct(
        public string $articleId,
        public string $userId,
    ) {
    }
}