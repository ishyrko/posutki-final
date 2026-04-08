<?php

declare(strict_types=1);

namespace App\Application\Query\Article\GetArticle;

use App\Application\DTO\ArticleDTO;
use App\Domain\Article\Repository\ArticleRepositoryInterface;
use App\Domain\Shared\ValueObject\Id;
use App\Domain\Shared\ValueObject\Slug;
use Psr\Log\LoggerInterface;

readonly class GetArticleHandler
{
    public function __construct(
        private ArticleRepositoryInterface $articleRepository,
        private LoggerInterface $logger,
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

        try {
            $article->incrementViews();
            $this->articleRepository->save($article);
        } catch (\Throwable $e) {
            $this->logger->error('Failed to persist article view increment', [
                'message' => $e->getMessage(),
                'articleId' => $article->getId()->getValue(),
            ]);
        }

        return ArticleDTO::fromEntity($article);
    }
}