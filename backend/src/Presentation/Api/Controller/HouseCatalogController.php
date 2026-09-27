<?php

declare(strict_types=1);

namespace App\Presentation\Api\Controller;

use App\Domain\Property\Entity\NationwideHouseCatalogContent;
use App\Domain\Property\Repository\NationwideHouseCatalogContentRepositoryInterface;
use App\Presentation\Api\Response\ApiResponse;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Routing\Annotation\Route;

class HouseCatalogController extends AbstractController
{
    public function __construct(
        private readonly NationwideHouseCatalogContentRepositoryInterface $nationwideHouseCatalogContentRepository,
    ) {
    }

    #[Route('/api/house-catalog', name: 'api_house_catalog_belarus', methods: ['GET'])]
    public function belarus(): JsonResponse
    {
        return $this->json(ApiResponse::success(
            $this->serialize($this->nationwideHouseCatalogContentRepository->findSingle()),
        ));
    }

    /**
     * @return array{catalogSeoVisible: bool, catalogSeoText: ?string, faq: list<array{question: string, answer: string}>}
     */
    private function serialize(?NationwideHouseCatalogContent $entity): array
    {
        if ($entity === null || !$entity->isCatalogSeoVisible()) {
            return [
                'catalogSeoVisible' => false,
                'catalogSeoText' => null,
                'faq' => [],
            ];
        }

        return [
            'catalogSeoVisible' => true,
            'catalogSeoText' => $entity->getCatalogSeoText(),
            'faq' => $entity->getCatalogFaq() ?? [],
        ];
    }
}
