<?php

declare(strict_types=1);

namespace App\Presentation\Api\Controller;

use App\Application\Command\CommandBusInterface;
use App\Application\Command\User\RequestPhoneVerification\RequestPhoneVerificationCommand;
use App\Application\Command\User\VerifyPhone\VerifyPhoneCommand;
use App\Application\Command\User\DeleteUserPhone\DeleteUserPhoneCommand;
use App\Application\Query\QueryBusInterface;
use App\Application\Query\User\GetUserPhones\GetUserPhonesQuery;
use App\Presentation\Api\Request\RequestPhoneVerificationRequest;
use App\Presentation\Api\Request\VerifyPhoneRequest;
use App\Presentation\Api\Response\ApiResponse;
use App\Domain\User\Entity\User;
use Symfony\Component\Security\Http\Attribute\CurrentUser;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

#[Route('/api/phones', name: 'api_phones_')]
class PhoneController extends AbstractController
{
    public function __construct(
        private readonly CommandBusInterface $commandBus,
        private readonly QueryBusInterface $queryBus,
    ) {
    }

    #[Route('', name: 'list', methods: ['GET'])]
    public function list(#[CurrentUser] ?User $user): JsonResponse
    {
        if (!$user) {
            return $this->json(ApiResponse::error('Требуется авторизация', 401), 401);
        }

        $query = new GetUserPhonesQuery((string) $user->getId()->getValue());
        $phones = $this->queryBus->ask($query);

        return $this->json(ApiResponse::success($phones));
    }

    #[Route('/request', name: 'request', methods: ['POST'])]
    public function requestVerification(
        RequestPhoneVerificationRequest $request,
        Request $httpRequest,
        #[CurrentUser] ?User $user,
    ): JsonResponse {
        if (!$user) {
            return $this->json(ApiResponse::error('Требуется авторизация', 401), 401);
        }

        $command = new RequestPhoneVerificationCommand(
            userId: (string) $user->getId()->getValue(),
            phone: $request->phone,
            ip: $httpRequest->getClientIp(),
        );

        $this->commandBus->dispatch($command);

        return $this->json(ApiResponse::success(['message' => 'Verification code sent']));
    }

    #[Route('/verify', name: 'verify', methods: ['POST'])]
    public function verify(
        VerifyPhoneRequest $request,
        #[CurrentUser] ?User $user,
    ): JsonResponse {
        if (!$user) {
            return $this->json(ApiResponse::error('Требуется авторизация', 401), 401);
        }

        $command = new VerifyPhoneCommand(
            userId: (string) $user->getId()->getValue(),
            phone: $request->phone,
            code: $request->code,
        );

        $this->commandBus->dispatch($command);

        return $this->json(ApiResponse::success(['message' => 'Телефон успешно подтверждён']));
    }

    #[Route('/{id}', name: 'delete', methods: ['DELETE'])]
    public function delete(string $id, #[CurrentUser] ?User $user): JsonResponse
    {
        if (!$user) {
            return $this->json(ApiResponse::error('Требуется авторизация', 401), 401);
        }

        $command = new DeleteUserPhoneCommand(
            userId: (string) $user->getId()->getValue(),
            phoneId: $id,
        );

        $this->commandBus->dispatch($command);

        return $this->json(ApiResponse::success(['message' => 'Телефон успешно удалён']));
    }
}
