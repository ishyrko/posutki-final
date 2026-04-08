<?php

declare(strict_types=1);

namespace App\Application\Query\Article\SearchArticles;

use App\Application\DTO\ArticleDTO;
use App\Domain\Article\Repository\ArticleRepositoryInterface;

readonly class SearchArticlesHandler
{
    public function __construct(
        private ArticleRepositoryInterface $articleRepository,
    ) {
    }

    public function __invoke(SearchArticlesQuery $query): array
    {
        $filters = [];

        if ($query->status !== null) {
            $filters['status'] = $query->status;
        }

        if ($query->categoryId !== null) {
            $filters['categoryId'] = $query->categoryId;
        }

        if ($query->categorySlug !== null) {
            $filters['categorySlug'] = $query->categorySlug;
        }

        if ($query->tag !== null) {
            $filters['tag'] = $query->tag;
        }

        if ($query->authorId !== null) {
            $filters['authorId'] = $query->authorId;
        }

        $articles = $this->articleRepository->search($filters, $query->page, $query->limit);

        return array_map(
            fn($article) => ArticleDTO::fromEntity($article),
            $articles
        );
    }
}