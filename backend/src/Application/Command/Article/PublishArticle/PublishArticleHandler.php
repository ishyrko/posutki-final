<?php

declare(strict_types=1);

namespace App\Application\Command\Article\PublishArticle;

use App\Domain\Article\Repository\ArticleRepositoryInterface;
use App\Domain\Shared\Exception\UnauthorizedException;
use App\Domain\Shared\ValueObject\Id;

readonly class PublishArticleHandler
{
    public function __construct(
        private ArticleRepositoryInterface $articleRepository,
    ) {
    }

    public function __invoke(PublishArticleCommand $command): void
    {
        // Find article
        $article = $this->articleRepository->findById(Id::fromString($command->articleId));
        
        if (!$article) {
            throw new \InvalidArgumentException('Статья не найдена');
        }

        // Check authorization
        if (!$article->isAuthoredBy($command->userId)) {
            throw new UnauthorizedException('Нет прав на публикацию этой статьи');
        }

        // Publish
        $article->publish();

        $this->articleRepository->save($article);
    }
}