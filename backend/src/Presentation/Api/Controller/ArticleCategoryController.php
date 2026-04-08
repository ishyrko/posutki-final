<?php

declare(strict_types=1);

namespace App\Presentation\Api\Controller;

use App\Domain\Article\Entity\ArticleCategory;
use App\Presentation\Api\Response\ApiResponse;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Routing\Annotation\Route;

#[Route('/api/article-categories', name: 'api_article_categories_')]
class ArticleCategoryController extends AbstractController
{
    public function __construct(
        private readonly EntityManagerInterface $em,
    ) {
    }

    #[Route('', name: 'list', methods: ['GET'])]
    public function list(): JsonResponse
    {
        $categories = $this->em->getRepository(ArticleCategory::class)
            ->findBy([], ['sortOrder' => 'ASC']);

        $data = array_map(fn(ArticleCategory $cat) => [
            'id' => $cat->getId(),
            'name' => $cat->getName(),
            'slug' => $cat->getSlug(),
            'sortOrder' => $cat->getSortOrder(),
        ], $categories);

        return $this->json(ApiResponse::success($data));
    }

    #[Route('/{slug}', name: 'get_by_slug', methods: ['GET'])]
    public function getBySlug(string $slug): JsonResponse
    {
        $category = $this->em->getRepository(ArticleCategory::class)
            ->findOneBy(['slug' => $slug]);

        if (!$category) {
            return $this->json(ApiResponse::error('Категория не найдена', 404), 404);
        }

        return $this->json(ApiResponse::success([
            'id' => $category->getId(),
            'name' => $category->getName(),
            'slug' => $category->getSlug(),
            'sortOrder' => $category->getSortOrder(),
        ]));
    }
}
