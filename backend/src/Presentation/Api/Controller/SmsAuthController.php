<?php

declare(strict_types=1);

namespace App\Presentation\Api\Controller;

use App\Application\Command\Auth\RequestSmsCode\RequestSmsCodeCommand;
use App\Application\Command\Auth\VerifySmsCode\VerifySmsCodeCommand;
use App\Application\Command\CommandBusInterface;
use App\Infrastructure\Recaptcha\RecaptchaVerifier;
use App\Presentation\Api\Request\RequestSmsCodeRequest;
use App\Presentation\Api\Request\VerifySmsCodeRequest;
use App\Presentation\Api\Response\ApiResponse;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Annotation\Route;

#[Route('/api/auth/sms', name: 'api_auth_sms_')]
class SmsAuthController extends AbstractController
{
    public function __construct(
        private readonly CommandBusInterface $commandBus,
        private readonly RecaptchaVerifier $recaptcha,
    ) {
    }

    #[Route('/request', name: 'request', methods: ['POST'])]
    public function requestCode(RequestSmsCodeRequest $request, Request $httpRequest): JsonResponse
    {
        $this->recaptcha->verify($request->recaptchaToken, $httpRequest->getClientIp());

        $this->commandBus->dispatch(
            new RequestSmsCodeCommand(
                phone: $request->phone,
                ip: $httpRequest->getClientIp(),
            )
        );

        return $this->json(ApiResponse::success([
            'message' => 'Код подтверждения отправлен',
        ]));
    }

    #[Route('/verify', name: 'verify', methods: ['POST'])]
    public function verifyCode(VerifySmsCodeRequest $request): JsonResponse
    {
        $token = $this->commandBus->dispatch(
            new VerifySmsCodeCommand(
                phone: $request->phone,
                code: $request->code,
            )
        );

        return $this->json(ApiResponse::success([
            'token' => $token,
        ]));
    }
}
