<?php

declare(strict_types=1);

namespace App\Presentation\Api\Controller;

use App\Application\Command\Article\CreateArticle\CreateArticleCommand;
use App\Application\Command\Article\UpdateArticle\UpdateArticleCommand;
use App\Application\Command\Article\DeleteArticle\DeleteArticleCommand;
use App\Application\Command\Article\PublishArticle\PublishArticleCommand;
use App\Application\Query\Article\GetArticle\GetArticleQuery;
use App\Application\Query\Article\SearchArticles\SearchArticlesQuery;
use App\Application\Command\CommandBusInterface;
use App\Application\Query\QueryBusInterface;
use App\Presentation\Api\Request\CreateArticleRequest;
use App\Presentation\Api\Request\UpdateArticleRequest;
use App\Presentation\Api\Response\ApiResponse;
use App\Domain\User\Entity\User;
use Symfony\Component\Security\Http\Attribute\CurrentUser;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

#[Route('/api/articles', name: 'api_articles_')]
class ArticleController extends AbstractController
{
    public function __construct(
        private readonly CommandBusInterface $commandBus,
        private readonly QueryBusInterface $queryBus,
    ) {
    }

    #[Route('', name: 'list', methods: ['GET'])]
    public function list(Request $request): JsonResponse
    {
        $query = new SearchArticlesQuery(
            status: $request->query->get('status', 'published'),
            categoryId: $request->query->getInt('categoryId') ?: null,
            categorySlug: $request->query->get('categorySlug'),
            tag: $request->query->get('tag'),
            authorId: $request->query->get('authorId'),
            page: $request->query->getInt('page', 1),
            limit: $request->query->getInt('limit', 20),
        );

        $articles = $this->queryBus->ask($query);

        return $this->json(
            ApiResponse::success($articles)
        );
    }

    #[Route('/{id}', name: 'get', methods: ['GET'], requirements: ['id' => '\d+'])]
    public function get(string $id): JsonResponse
    {
        $query = new GetArticleQuery(id: $id);
        $article = $this->queryBus->ask($query);

        if (!$article) {
            return $this->json(
                ApiResponse::error('Статья не найдена', 404),
                404
            );
        }

        return $this->json(
            ApiResponse::success($article)
        );
    }

    #[Route('/{slug}', name: 'get_by_slug', methods: ['GET'])]
    public function getBySlug(string $slug): JsonResponse
    {
        $query = new GetArticleQuery(slug: $slug);
        $article = $this->queryBus->ask($query);

        if (!$article) {
            return $this->json(
                ApiResponse::error('Статья не найдена', 404),
                404
            );
        }

        return $this->json(
            ApiResponse::success($article)
        );
    }

    #[Route('', name: 'create', methods: ['POST'])]
    public function create(
        CreateArticleRequest $request,
        #[CurrentUser] ?User $user
    ): JsonResponse {
        if (!$user) {
            return $this->json(
                ApiResponse::error('Требуется авторизация', 401),
                401
            );
        }

        $command = new CreateArticleCommand(
            authorId: (string) $user->getId()->getValue(),
            title: $request->title,
            content: $request->content,
            excerpt: $request->excerpt,
            coverImage: $request->coverImage,
            categoryId: $request->categoryId,
            tags: $request->tags,
        );

        $articleId = $this->commandBus->dispatch($command);

        return $this->json(
            ApiResponse::success([
                'message' => 'Статья успешно создана',
                'articleId' => $articleId
            ]),
            Response::HTTP_CREATED
        );
    }

    #[Route('/{id}', name: 'update', methods: ['PUT', 'PATCH'])]
    public function update(
        string $id,
        UpdateArticleRequest $request,
        #[CurrentUser] ?User $user
    ): JsonResponse {
        if (!$user) {
            return $this->json(
                ApiResponse::error('Требуется авторизация', 401),
                401
            );
        }

        $command = new UpdateArticleCommand(
            articleId: $id,
            userId: (string) $user->getId()->getValue(),
            title: $request->title,
            content: $request->content,
            excerpt: $request->excerpt,
            coverImage: $request->coverImage,
            categoryId: $request->categoryId,
            tags: $request->tags,
        );

        $this->commandBus->dispatch($command);

        return $this->json(
            ApiResponse::success(['message' => 'Статья успешно обновлена'])
        );
    }

    #[Route('/{id}', name: 'delete', methods: ['DELETE'])]
    public function delete(
        string $id,
        #[CurrentUser] ?User $user
    ): JsonResponse {
        if (!$user) {
            return $this->json(
                ApiResponse::error('Требуется авторизация', 401),
                401
            );
        }

        $command = new DeleteArticleCommand(
            articleId: $id,
            userId: (string) $user->getId()->getValue(),
        );

        $this->commandBus->dispatch($command);

        return $this->json(
            ApiResponse::success(['message' => 'Статья успешно удалена'])
        );
    }

    #[Route('/{id}/publish', name: 'publish', methods: ['POST'])]
    public function publish(
        string $id,
        #[CurrentUser] ?User $user
    ): JsonResponse {
        if (!$user) {
            return $this->json(
                ApiResponse::error('Требуется авторизация', 401),
                401
            );
        }

        $command = new PublishArticleCommand(
            articleId: $id,
            userId: (string) $user->getId()->getValue(),
        );

        $this->commandBus->dispatch($command);

        return $this->json(
            ApiResponse::success(['message' => 'Статья успешно опубликована'])
        );
    }
}