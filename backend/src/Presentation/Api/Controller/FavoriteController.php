<?php

declare(strict_types=1);

namespace App\Presentation\Api\Controller;

use App\Application\Command\Favorite\AddFavorite\AddFavoriteCommand;
use App\Application\Command\Favorite\RemoveFavorite\RemoveFavoriteCommand;
use App\Application\Query\Favorite\GetFavorites\GetFavoritesQuery;
use App\Application\Query\Favorite\GetFavoriteIds\GetFavoriteIdsQuery;
use App\Application\Command\CommandBusInterface;
use App\Application\Query\QueryBusInterface;
use App\Presentation\Api\Response\ApiResponse;
use App\Domain\User\Entity\User;
use Symfony\Component\Security\Http\Attribute\CurrentUser;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

#[Route('/api/favorites', name: 'api_favorites_')]
class FavoriteController extends AbstractController
{
    public function __construct(
        private readonly CommandBusInterface $commandBus,
        private readonly QueryBusInterface $queryBus,
    ) {
    }

    #[Route('', name: 'list', methods: ['GET'])]
    public function list(Request $request, #[CurrentUser] ?User $user): JsonResponse
    {
        if (!$user) {
            return $this->json(ApiResponse::error('Требуется авторизация', 401), 401);
        }

        $query = new GetFavoritesQuery(
            userId: (string) $user->getId()->getValue(),
            page: $request->query->getInt('page', 1),
            limit: $request->query->getInt('limit', 20),
        );

        $properties = $this->queryBus->ask($query);

        return $this->json(ApiResponse::success($properties));
    }

    #[Route('/ids', name: 'ids', methods: ['GET'])]
    public function ids(#[CurrentUser] ?User $user): JsonResponse
    {
        if (!$user) {
            return $this->json(ApiResponse::error('Требуется авторизация', 401), 401);
        }

        $query = new GetFavoriteIdsQuery(
            userId: (string) $user->getId()->getValue(),
        );

        $ids = $this->queryBus->ask($query);

        return $this->json(ApiResponse::success($ids));
    }

    #[Route('/{propertyId}', name: 'add', methods: ['POST'])]
    public function add(string $propertyId, #[CurrentUser] ?User $user): JsonResponse
    {
        if (!$user) {
            return $this->json(ApiResponse::error('Требуется авторизация', 401), 401);
        }

        $command = new AddFavoriteCommand(
            userId: (string) $user->getId()->getValue(),
            propertyId: $propertyId,
        );

        $this->commandBus->dispatch($command);

        return $this->json(
            ApiResponse::success(['message' => 'Добавлено в избранное']),
            Response::HTTP_CREATED
        );
    }

    #[Route('/{propertyId}', name: 'remove', methods: ['DELETE'])]
    public function remove(string $propertyId, #[CurrentUser] ?User $user): JsonResponse
    {
        if (!$user) {
            return $this->json(ApiResponse::error('Требуется авторизация', 401), 401);
        }

        $command = new RemoveFavoriteCommand(
            userId: (string) $user->getId()->getValue(),
            propertyId: $propertyId,
        );

        $this->commandBus->dispatch($command);

        return $this->json(ApiResponse::success(['message' => 'Удалено из избранного']));
    }
}
