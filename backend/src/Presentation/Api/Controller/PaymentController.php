<?php

declare(strict_types=1);

namespace App\Presentation\Api\Controller;

use App\Application\Command\CommandBusInterface;
use App\Application\Command\Payment\ConfirmPlacementPayment\ConfirmPlacementPaymentCommand;
use App\Application\Command\Payment\CreatePlacementPayment\CreatePlacementPaymentCommand;
use App\Application\Command\Payment\ProcessBePaidWebhook\ProcessBePaidWebhookCommand;
use App\Domain\Property\Repository\PropertyPlacementPurchaseRepositoryInterface;
use App\Domain\Shared\Exception\DomainException;
use App\Domain\User\Entity\User;
use App\Infrastructure\Payment\BePaid\BePaidSignatureVerifier;
use App\Presentation\Api\Response\ApiResponse;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\CurrentUser;

final class PaymentController extends AbstractController
{
    public function __construct(
        private readonly PropertyPlacementPurchaseRepositoryInterface $purchaseRepository,
        private readonly CommandBusInterface $commandBus,
        private readonly BePaidSignatureVerifier $signatureVerifier,
    ) {
    }

    #[Route('/api/placement-purchases/{id}/payments', name: 'api_placement_purchase_payments_create', methods: ['POST'], requirements: ['id' => '\d+'])]
    public function createPayment(string $id, Request $request, #[CurrentUser] ?User $user): JsonResponse
    {
        if (!$user) {
            return $this->json(ApiResponse::error('Требуется авторизация', 401), 401);
        }

        $purchase = $this->purchaseRepository->findById((int) $id);
        if ($purchase === null) {
            throw new DomainException('Заявка не найдена');
        }
        if (!$purchase->getOwnerId()->equals($user->getId())) {
            throw new DomainException('Нет прав на эту заявку');
        }

        $result = $this->commandBus->dispatch(new CreatePlacementPaymentCommand(
            purchaseId: (int) $id,
            userId: (string) $user->getId()->getValue(),
            customerIp: $request->getClientIp(),
            customerEmail: $user->getEmail()?->getValue(),
        ));

        return $this->json(ApiResponse::success($result), 201);
    }

    #[Route('/api/placement-purchases/{id}/payments/confirm', name: 'api_placement_purchase_payments_confirm', methods: ['POST'], requirements: ['id' => '\d+'])]
    public function confirmPayment(string $id, Request $request, #[CurrentUser] ?User $user): JsonResponse
    {
        if (!$user) {
            return $this->json(ApiResponse::error('Требуется авторизация', 401), 401);
        }

        $payload = json_decode($request->getContent(), true);
        if (!is_array($payload)) {
            return $this->json(ApiResponse::error('Некорректное тело запроса', 400), 400);
        }

        $token = isset($payload['token']) && is_string($payload['token']) ? trim($payload['token']) : '';
        if ($token === '') {
            return $this->json(ApiResponse::error('Укажите token', 400), 400);
        }

        $result = $this->commandBus->dispatch(new ConfirmPlacementPaymentCommand(
            purchaseId: (int) $id,
            userId: (string) $user->getId()->getValue(),
            checkoutToken: $token,
        ));

        return $this->json(ApiResponse::success($result));
    }

    #[Route('/api/webhooks/bepaid', name: 'api_webhooks_bepaid', methods: ['POST'])]
    public function bePaidWebhook(Request $request): JsonResponse
    {
        $rawBody = $request->getContent();
        if ($rawBody === '') {
            return $this->json(ApiResponse::error('Пустое тело запроса', 400), 400);
        }

        $payload = json_decode($rawBody, true);
        if (!is_array($payload)) {
            return $this->json(ApiResponse::error('Некорректный JSON', 400), 400);
        }

        $signatureHeader = $request->headers->get('Content-Signature');
        $signatureValid = $this->signatureVerifier->verify($rawBody, $signatureHeader);

        $this->commandBus->dispatch(new ProcessBePaidWebhookCommand(
            payload: $payload,
            rawBody: $rawBody,
            signatureValid: $signatureValid,
        ));

        if (!$signatureValid) {
            return $this->json(ApiResponse::error('Неверная подпись', 403), 403);
        }

        return $this->json(ApiResponse::success(['received' => true]));
    }
}
