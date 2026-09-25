<?php

declare(strict_types=1);

namespace App\Presentation\Api\Controller;

use App\Domain\Property\Entity\RegionCatalogContent;
use App\Domain\Property\Repository\RegionCatalogContentRepositoryInterface;
use App\Domain\Property\Repository\RegionRepositoryInterface;
use App\Presentation\Api\Response\ApiResponse;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Routing\Annotation\Route;

#[Route('/api/regions', name: 'api_regions_')]
class RegionController extends AbstractController
{
    public function __construct(
        private readonly RegionRepositoryInterface $regionRepository,
        private readonly RegionCatalogContentRepositoryInterface $regionCatalogContentRepository,
    ) {
    }

    #[Route('/{slug}', name: 'get_by_slug', methods: ['GET'])]
    public function getBySlug(string $slug): JsonResponse
    {
        $region = $this->regionRepository->findBySlug($slug);
        if ($region === null) {
            return $this->json(ApiResponse::error('Область не найдена', 404), 404);
        }

        $content = $this->regionCatalogContentRepository->findByRegionId($region->getId());

        return $this->json(ApiResponse::success([
            'id' => $region->getId(),
            'name' => $region->getName(),
            'slug' => $region->getSlug(),
            'code' => $region->getCode(),
            ...$this->serializeCatalogSeoContent($content),
        ]));
    }

    /**
     * @return array{catalogSeoVisible: bool, catalogSeoText: ?string, faq: list<array{question: string, answer: string}>}
     */
    private function serializeCatalogSeoContent(?RegionCatalogContent $entity): array
    {
        if ($entity === null) {
            return [
                'catalogSeoVisible' => false,
                'catalogSeoText' => null,
                'faq' => [],
            ];
        }

        $visible = $entity->isCatalogSeoVisible();

        return [
            'catalogSeoVisible' => $visible,
            'catalogSeoText' => $visible ? $entity->getCatalogSeoText() : null,
            'faq' => $visible ? ($entity->getCatalogFaq() ?? []) : [],
        ];
    }
}
