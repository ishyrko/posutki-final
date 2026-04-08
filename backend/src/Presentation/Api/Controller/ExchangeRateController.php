<?php

declare(strict_types=1);

namespace App\Presentation\Api\Controller;

use App\Infrastructure\Service\ExchangeRateService;
use App\Presentation\Api\Response\ApiResponse;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Routing\Annotation\Route;

#[Route('/api/exchange-rates', name: 'api_exchange_rates_')]
class ExchangeRateController extends AbstractController
{
    public function __construct(
        private readonly ExchangeRateService $exchangeRateService,
    ) {
    }

    #[Route('', name: 'list', methods: ['GET'])]
    public function list(): JsonResponse
    {
        $rates = $this->exchangeRateService->getRates();

        return $this->json(ApiResponse::success($rates));
    }
}
