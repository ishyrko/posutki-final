<?php

declare(strict_types=1);

namespace App\Application\Command\Article\UpdateArticle;

use App\Domain\Article\Entity\ArticleCategory;
use App\Domain\Article\Repository\ArticleRepositoryInterface;
use App\Domain\Shared\Exception\UnauthorizedException;
use App\Domain\Shared\ValueObject\Id;
use App\Domain\Shared\ValueObject\Slug;
use App\Infrastructure\Service\SlugGenerator;
use Doctrine\ORM\EntityManagerInterface;

readonly class UpdateArticleHandler
{
    public function __construct(
        private ArticleRepositoryInterface $articleRepository,
        private SlugGenerator $slugGenerator,
        private EntityManagerInterface $em,
    ) {
    }

    public function __invoke(UpdateArticleCommand $command): void
    {
        $article = $this->articleRepository->findById(Id::fromString($command->articleId));
        
        if (!$article) {
            throw new \InvalidArgumentException('Статья не найдена');
        }

        if (!$article->isAuthoredBy($command->userId)) {
            throw new UnauthorizedException('Нет прав на изменение этой статьи');
        }

        $newSlug = null;
        if ($command->title !== null && $command->title !== $article->getTitle()) {
            $baseSlug = $this->slugGenerator->generate($command->title);
            $uniqueSlug = $this->slugGenerator->ensureUnique($baseSlug, $this->articleRepository);
            $newSlug = Slug::fromString($uniqueSlug);
        }

        $category = $command->categoryId !== null
            ? $this->em->find(ArticleCategory::class, $command->categoryId)
            : null;

        $article->update(
            title: $command->title,
            slug: $newSlug,
            content: $command->content,
            excerpt: $command->excerpt,
            coverImage: $command->coverImage,
            category: $category,
            tags: $command->tags,
        );

        $this->articleRepository->save($article);
    }
}