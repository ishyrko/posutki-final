<?php

declare(strict_types=1);

namespace App\Presentation\Api\Controller;

use App\Application\Command\CommandBusInterface;
use App\Application\Command\User\ConfirmEmailChange\ConfirmEmailChangeCommand;
use App\Application\Command\User\ConfirmPasswordReset\ConfirmPasswordResetCommand;
use App\Application\Command\User\RequestPasswordReset\RequestPasswordResetCommand;
use App\Application\Command\User\SendEmailVerification\SendEmailVerificationCommand;
use App\Application\Command\User\VerifyEmail\VerifyEmailCommand;
use App\Presentation\Api\Request\ResendVerificationRequest;
use App\Presentation\Api\Request\VerifyEmailTokenRequest;
use App\Application\Query\User\GetUserProfile\GetUserProfileQuery;
use App\Domain\User\Entity\User;
use App\Domain\User\Repository\UserRepositoryInterface;
use App\Domain\User\ValueObject\Email;
use App\Infrastructure\Bus\QueryBus;
use App\Presentation\Api\Response\ApiResponse;
use Lexik\Bundle\JWTAuthenticationBundle\Services\JWTTokenManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Http\Attribute\CurrentUser;

#[Route('/api/auth', name: 'api_auth_')]
class AuthController extends AbstractController
{
    public function __construct(
        private readonly QueryBus $queryBus,
        private readonly CommandBusInterface $commandBus,
        private readonly JWTTokenManagerInterface $jwtManager,
        private readonly UserRepositoryInterface $userRepository,
    ) {
    }

    #[Route('/login', name: 'login', methods: ['POST'])]
    public function login(): JsonResponse
    {
        // This method is handled by security.yaml json_login
        // It won't be called, but the route must exist
        throw new \LogicException('This method should not be reached. Login is handled by Symfony security.');
    }

    #[Route('/me', name: 'me', methods: ['GET'])]
    public function me(#[CurrentUser] ?User $user): JsonResponse
    {
        if (!$user) {
            return $this->json([
                'error' => 'Не авторизован',
            ], Response::HTTP_UNAUTHORIZED);
        }

        $query = new GetUserProfileQuery((string) $user->getId()->getValue());
        $userDTO = $this->queryBus->ask($query);

        return $this->json(['data' => $userDTO]);
    }

    #[Route('/google', name: 'google', methods: ['POST'])]
    public function googleAuth(Request $request): JsonResponse
    {
        $data = json_decode($request->getContent(), true);
        $idToken = $data['idToken'] ?? null;

        if (!$idToken) {
            return $this->json(
                ApiResponse::error('Требуется токен Google', 400),
                400
            );
        }

        $googleClientId = $this->getParameter('app.google_client_id');

        $client = new \Google_Client(['client_id' => $googleClientId]);
        $payload = $client->verifyIdToken($idToken);

        if (!$payload) {
            return $this->json(
                ApiResponse::error('Недействительный токен Google', 401),
                401
            );
        }

        $googleId = $payload['sub'];
        $email = $payload['email'] ?? null;
        $firstName = $payload['given_name'] ?? $payload['name'] ?? 'User';
        $lastName = $payload['family_name'] ?? '';
        $avatar = $payload['picture'] ?? null;

        if (!$email) {
            return $this->json(
                ApiResponse::error('Google не передал email', 400),
                400
            );
        }

        $user = $this->userRepository->findByEmail(Email::fromString($email));

        if ($user) {
            if (!$user->getGoogleId()) {
                $user->setGoogleId($googleId);
                $this->userRepository->save($user);
            }
        } else {
            $user = User::registerViaGoogle(
                Email::fromString($email),
                $googleId,
                $firstName,
                $lastName ?: $firstName,
                $avatar,
            );
            $this->userRepository->save($user);
        }

        $token = $this->jwtManager->create($user);

        return $this->json(ApiResponse::success(['token' => $token]));
    }

    #[Route('/verify-email', name: 'verify_email', methods: ['POST'])]
    public function verifyEmail(VerifyEmailTokenRequest $request): JsonResponse
    {
        $this->commandBus->dispatch(new VerifyEmailCommand($request->email, $request->token));

        return $this->json(ApiResponse::success([
            'message' => 'Email успешно подтверждён.',
        ]));
    }

    #[Route('/resend-verification', name: 'resend_verification', methods: ['POST'])]
    public function resendVerification(ResendVerificationRequest $request): JsonResponse
    {
        $this->commandBus->dispatch(new SendEmailVerificationCommand($request->email));

        return $this->json(ApiResponse::success([
            'message' => 'Если аккаунт с таким email существует и требуется подтверждение, мы отправили письмо.',
        ]));
    }

    #[Route('/confirm-email-change', name: 'confirm_email_change', methods: ['POST'])]
    public function confirmEmailChange(VerifyEmailTokenRequest $request): JsonResponse
    {
        $this->commandBus->dispatch(new ConfirmEmailChangeCommand($request->email, $request->token));

        return $this->json(ApiResponse::success([
            'message' => 'Email успешно подтверждён.',
        ]));
    }

    #[Route('/reset-password', name: 'reset_password', methods: ['POST'])]
    public function resetPassword(Request $request): JsonResponse
    {
        $data = json_decode($request->getContent(), true);
        $email = $data['email'] ?? null;

        if (!$email) {
            return $this->json(
                ApiResponse::error('Укажите email', 400),
                400
            );
        }

        $this->commandBus->dispatch(new RequestPasswordResetCommand($email));

        return $this->json(ApiResponse::success([
            'message' => 'Если аккаунт с таким email существует, мы отправили инструкции по сбросу пароля.',
        ]));
    }

    #[Route('/reset-password/confirm', name: 'reset_password_confirm', methods: ['POST'])]
    public function confirmResetPassword(Request $request): JsonResponse
    {
        $data = json_decode($request->getContent(), true);
        $email = $data['email'] ?? null;
        $token = $data['token'] ?? null;
        $newPassword = $data['password'] ?? null;

        if (!$email || !$token || !$newPassword) {
            return $this->json(
                ApiResponse::error('Укажите email, токен и пароль', 400),
                400
            );
        }

        try {
            $this->commandBus->dispatch(new ConfirmPasswordResetCommand($email, $token, $newPassword));
        } catch (\Exception $e) {
            return $this->json(
                ApiResponse::error('Недействительная или просроченная ссылка для сброса пароля', 400),
                400
            );
        }

        return $this->json(ApiResponse::success([
            'message' => 'Пароль успешно изменён.',
        ]));
    }

    #[Route('/refresh', name: 'refresh', methods: ['POST'])]
    public function refresh(Request $request): JsonResponse
    {
        // TODO: Implement refresh token logic
        // For now, return not implemented
        return $this->json([
            'error' => 'Обновление токена пока не реализовано',
        ], Response::HTTP_NOT_IMPLEMENTED);
    }
}