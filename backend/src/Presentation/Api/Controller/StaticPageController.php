<?php

declare(strict_types=1);

namespace App\Presentation\Api\Controller;

use App\Application\Query\QueryBusInterface;
use App\Application\Query\StaticPage\GetStaticPage\GetStaticPageQuery;
use App\Presentation\Api\Response\ApiResponse;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Routing\Annotation\Route;

#[Route('/api/static-pages', name: 'api_static_pages_')]
class StaticPageController extends AbstractController
{
    public function __construct(
        private readonly QueryBusInterface $queryBus,
    ) {
    }

    #[Route('/{slug}', name: 'get', methods: ['GET'])]
    public function get(string $slug): JsonResponse
    {
        $query = new GetStaticPageQuery(slug: $slug);
        $page = $this->queryBus->ask($query);

        if ($page === null) {
            return $this->json(
                ApiResponse::error('Страница не найдена', 404),
                404
            );
        }

        return $this->json(
            ApiResponse::success($page)
        );
    }
}
