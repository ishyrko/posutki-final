<?php

declare(strict_types=1);

namespace App\Application\Command\Article\DeleteArticle;

use App\Domain\Article\Repository\ArticleRepositoryInterface;
use App\Domain\Shared\Exception\UnauthorizedException;
use App\Domain\Shared\ValueObject\Id;

readonly class DeleteArticleHandler
{
    public function __construct(
        private ArticleRepositoryInterface $articleRepository,
    ) {
    }

    public function __invoke(DeleteArticleCommand $command): void
    {
        // Find article
        $article = $this->articleRepository->findById(Id::fromString($command->articleId));
        
        if (!$article) {
            throw new \InvalidArgumentException('Статья не найдена');
        }

        // Check authorization
        if (!$article->isAuthoredBy($command->userId)) {
            throw new UnauthorizedException('Нет прав на удаление этой статьи');
        }

        // Soft delete
        $article->delete();

        $this->articleRepository->save($article);
    }
}