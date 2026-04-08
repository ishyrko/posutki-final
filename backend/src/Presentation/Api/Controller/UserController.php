<?php

declare(strict_types=1);

namespace App\Presentation\Api\Controller;

use App\Application\Command\User\RegisterUser\RegisterUserCommand;
use App\Application\Query\User\GetUserProfile\GetUserProfileQuery;
use App\Application\Command\CommandBusInterface;
use App\Application\Query\QueryBusInterface;
use App\Presentation\Api\Request\RegisterUserRequest;
use App\Presentation\Api\Request\UpdateUserProfileRequest;
use App\Presentation\Api\Request\ChangePasswordRequest;
use App\Application\Command\User\UpdateUserProfile\UpdateUserProfileCommand;
use App\Application\Command\User\ChangePassword\ChangePasswordCommand;
use App\Application\Command\User\RequestEmailChange\RequestEmailChangeCommand;
use App\Presentation\Api\Request\UpdateEmailRequest;
use App\Domain\User\Entity\User;
use Symfony\Component\Security\Http\Attribute\CurrentUser;
use App\Presentation\Api\Response\ApiResponse;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

#[Route('/api', name: 'api_')]
class UserController extends AbstractController
{
    public function __construct(
        private readonly CommandBusInterface $commandBus,
        private readonly QueryBusInterface $queryBus,
    ) {
    }

    #[Route('/register', name: 'register', methods: ['POST'])]
    public function register(RegisterUserRequest $request): JsonResponse
    {
        // Validation happens automatically via RequestDTOResolver
        
        $command = new RegisterUserCommand(
            email: $request->email,
            password: $request->password,
            firstName: $request->firstName,
            lastName: $request->lastName,
            phone: $request->phone,
        );

        $userId = $this->commandBus->dispatch($command);

        return $this->json(
            ApiResponse::success([
                'message' => 'Регистрация прошла успешно',
                'userId' => $userId,
            ]),
            Response::HTTP_CREATED
        );
    }

    #[Route('/users/{id}', name: 'get_user', methods: ['GET'], requirements: ['id' => '\d+'])]
    public function getUserProfile(string $id): JsonResponse
    {
        $query = new GetUserProfileQuery($id);
        $userDTO = $this->queryBus->ask($query);

        return $this->json(ApiResponse::success($userDTO));
    }

    #[Route('/users/update-email', name: 'update_email', methods: ['POST'])]
    public function updateEmail(
        UpdateEmailRequest $request,
        #[CurrentUser] ?User $user
    ): JsonResponse {
        if (!$user) {
            return $this->json(
                ApiResponse::error('Требуется авторизация', 401),
                401
            );
        }

        $this->commandBus->dispatch(new RequestEmailChangeCommand(
            userId: (string) $user->getId()->getValue(),
            email: $request->email,
        ));

        return $this->json(
            ApiResponse::success([
                'message' => 'Проверьте почту и перейдите по ссылке для подтверждения email.',
            ])
        );
    }

    #[Route('/users/profile', name: 'update_profile', methods: ['PUT', 'PATCH'])]
    public function updateProfile(
        UpdateUserProfileRequest $request,
        #[CurrentUser] ?User $user
    ): JsonResponse {
        if (!$user) {
            return $this->json(
                ApiResponse::error('Требуется авторизация', 401),
                401
            );
        }

        $command = new UpdateUserProfileCommand(
            userId: (string) $user->getId()->getValue(),
            firstName: $request->firstName,
            lastName: $request->lastName,
            phone: $request->phone,
            avatar: $request->avatar,
        );

        $this->commandBus->dispatch($command);

        return $this->json(
            ApiResponse::success(['message' => 'Профиль успешно обновлён'])
        );
    }

    #[Route('/users/change-password', name: 'change_password', methods: ['POST'])]
    public function changePassword(
        ChangePasswordRequest $request,
        #[CurrentUser] ?User $user
    ): JsonResponse {
        if (!$user) {
            return $this->json(
                ApiResponse::error('Требуется авторизация', 401),
                401
            );
        }

        $command = new ChangePasswordCommand(
            userId: (string) $user->getId()->getValue(),
            currentPassword: $request->currentPassword,
            newPassword: $request->newPassword,
        );

        $this->commandBus->dispatch($command);

        return $this->json(
            ApiResponse::success(['message' => 'Пароль успешно изменён'])
        );
    }
}