<?php

declare(strict_types=1);

namespace App\Application\Command\Article\DeleteArticle;

readonly class DeleteArticleCommand
{
    public function __construct(
        public string $articleId,
        public string $userId,
    ) {
    }
}