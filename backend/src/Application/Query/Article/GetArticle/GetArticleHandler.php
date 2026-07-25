<?php

declare(strict_types=1);

namespace App\Application\Query\Article\GetArticle;

use App\Application\DTO\ArticleDTO;
use App\Domain\Article\Repository\ArticleRepositoryInterface;
use App\Domain\Shared\ValueObject\Id;
use App\Domain\Shared\ValueObject\Slug;

readonly class GetArticleHandler
{
    public function __construct(
        private ArticleRepositoryInterface $articleRepository,
    ) {
    }

    public function __invoke(GetArticleQuery $query): ?ArticleDTO
    {
        $article = null;

        if ($query->id !== null) {
            $article = $this->articleRepository->findById(Id::fromString($query->id));
        } elseif ($query->slug !== null) {
            $article = $this->articleRepository->findBySlug(Slug::fromString($query->slug));
        }

        if (!$article) {
            return null;
        }

        return ArticleDTO::fromEntity($article);
    }
}