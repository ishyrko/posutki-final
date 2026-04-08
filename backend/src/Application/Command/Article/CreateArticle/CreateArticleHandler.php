<?php

declare(strict_types=1);

namespace App\Application\Command\Article\CreateArticle;

use App\Domain\Article\Entity\Article;
use App\Domain\Article\Entity\ArticleCategory;
use App\Domain\Article\Repository\ArticleRepositoryInterface;
use App\Domain\Shared\ValueObject\Id;
use App\Domain\Shared\ValueObject\Slug;
use App\Infrastructure\Service\SlugGenerator;
use Doctrine\ORM\EntityManagerInterface;

readonly class CreateArticleHandler
{
    public function __construct(
        private ArticleRepositoryInterface $articleRepository,
        private SlugGenerator $slugGenerator,
        private EntityManagerInterface $em,
    ) {
    }

    public function __invoke(CreateArticleCommand $command): int
    {
        $baseSlug = $this->slugGenerator->generate($command->title);
        $uniqueSlug = $this->slugGenerator->ensureUnique($baseSlug, $this->articleRepository);

        $category = $command->categoryId !== null
            ? $this->em->find(ArticleCategory::class, $command->categoryId)
            : null;

        $article = new Article(
            authorId: Id::fromString($command->authorId),
            title: $command->title,
            slug: Slug::fromString($uniqueSlug),
            content: $command->content,
            excerpt: $command->excerpt,
            coverImage: $command->coverImage,
            category: $category,
            tags: $command->tags,
        );

        $this->articleRepository->save($article);

        return $article->getId()->getValue();
    }
}