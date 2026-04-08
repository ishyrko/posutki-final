<?php

declare(strict_types=1);

namespace App\Domain\Article\Repository;

use App\Domain\Article\Entity\Article;
use App\Domain\Shared\ValueObject\{Id, Slug};

interface ArticleRepositoryInterface
{
    public function save(Article $article): void;

    public function findById(Id $id): ?Article;

    public function findBySlug(Slug $slug): ?Article;

    public function findPublished(int $page = 1, int $limit = 20): array;

    /**
     * @return Article[]
     */
    public function search(array $filters, int $page, int $limit): array;

    public function delete(Article $article): void;
}