<?php

declare(strict_types=1);

namespace App\Presentation\Api\Controller;

use App\Application\Command\CommandBusInterface;
use App\Application\Command\ContactFeedback\Submit\SubmitFeedbackCommand;
use App\Presentation\Api\Response\ApiResponse;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

#[Route('/api/contact', name: 'api_contact_')]
class ContactController extends AbstractController
{
    public function __construct(
        private readonly CommandBusInterface $commandBus,
    ) {
    }

    #[Route('', name: 'submit', methods: ['POST'])]
    public function submit(Request $request): JsonResponse
    {
        $data = json_decode($request->getContent(), true);
        if (!is_array($data)) {
            return $this->json(ApiResponse::error('Некорректные данные запроса', 422), 422);
        }

        $name = trim((string) ($data['name'] ?? ''));
        $email = trim((string) ($data['email'] ?? ''));
        $subject = trim((string) ($data['subject'] ?? ''));
        $message = trim((string) ($data['message'] ?? ''));

        if ($name === '' || $email === '' || $subject === '' || $message === '') {
            return $this->json(
                ApiResponse::error('Заполните все поля', 422),
                422,
            );
        }

        if (filter_var($email, FILTER_VALIDATE_EMAIL) === false) {
            return $this->json(
                ApiResponse::error('Неверный формат email', 422),
                422,
            );
        }

        $command = new SubmitFeedbackCommand(
            name: $name,
            email: $email,
            subject: $subject,
            message: $message,
        );

        $result = $this->commandBus->dispatch($command);

        return $this->json(
            ApiResponse::success($result),
            Response::HTTP_CREATED,
        );
    }
}
