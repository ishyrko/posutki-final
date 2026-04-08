<?php

declare(strict_types=1);

namespace App\Presentation\Api\Controller;

use App\Application\Command\CommandBusInterface;
use App\Application\Command\Message\MarkRead\MarkReadCommand;
use App\Application\Command\Message\SendMessage\SendMessageCommand;
use App\Application\Query\Message\GetConversations\GetConversationsQuery;
use App\Application\Query\Message\GetMessages\GetMessagesQuery;
use App\Application\Query\QueryBusInterface;
use App\Domain\Message\Repository\ConversationRepositoryInterface;
use App\Domain\User\Entity\User;
use App\Presentation\Api\Response\ApiResponse;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Http\Attribute\CurrentUser;

#[Route('/api/messages', name: 'api_messages_')]
class MessageController extends AbstractController
{
    public function __construct(
        private readonly CommandBusInterface $commandBus,
        private readonly QueryBusInterface $queryBus,
        private readonly ConversationRepositoryInterface $conversationRepository,
    ) {
    }

    #[Route('/conversations', name: 'conversations', methods: ['GET'])]
    public function conversations(Request $request, #[CurrentUser] ?User $user): JsonResponse
    {
        if (!$user) {
            return $this->json(ApiResponse::error('Требуется авторизация', 401), 401);
        }

        $query = new GetConversationsQuery(
            userId: (string) $user->getId()->getValue(),
            page: $request->query->getInt('page', 1),
            limit: $request->query->getInt('limit', 20),
        );

        $result = $this->queryBus->ask($query);

        return $this->json(ApiResponse::paginated(
            $result['items'],
            $result['total'],
            $result['page'],
            $result['limit'],
        ));
    }

    #[Route('/conversations/{id}', name: 'conversation_messages', methods: ['GET'])]
    public function messages(string $id, Request $request, #[CurrentUser] ?User $user): JsonResponse
    {
        if (!$user) {
            return $this->json(ApiResponse::error('Требуется авторизация', 401), 401);
        }

        $query = new GetMessagesQuery(
            conversationId: $id,
            userId: (string) $user->getId()->getValue(),
            page: $request->query->getInt('page', 1),
            limit: $request->query->getInt('limit', 50),
        );

        $result = $this->queryBus->ask($query);

        return $this->json(ApiResponse::paginated(
            $result['items'],
            $result['total'],
            $result['page'],
            $result['limit'],
        ));
    }

    #[Route('/send', name: 'send', methods: ['POST'])]
    public function send(Request $request, #[CurrentUser] ?User $user): JsonResponse
    {
        if (!$user) {
            return $this->json(ApiResponse::error('Требуется авторизация', 401), 401);
        }

        $data = json_decode($request->getContent(), true);

        $text = trim($data['text'] ?? '');
        if ($text === '') {
            return $this->json(ApiResponse::error('Укажите текст сообщения', 422), 422);
        }

        $propertyId = $data['propertyId'] ?? null;
        $conversationId = $data['conversationId'] ?? null;

        if ($propertyId === null && $conversationId === null) {
            return $this->json(
                ApiResponse::error('Укажите propertyId или conversationId', 422),
                422
            );
        }

        $command = new SendMessageCommand(
            senderId: (string) $user->getId()->getValue(),
            propertyId: $propertyId !== null ? (string) $propertyId : '',
            text: $text,
            conversationId: $conversationId !== null ? (string) $conversationId : null,
        );

        $result = $this->commandBus->dispatch($command);

        return $this->json(
            ApiResponse::success($result),
            Response::HTTP_CREATED,
        );
    }

    #[Route('/conversations/{id}/read', name: 'mark_read', methods: ['POST'])]
    public function markRead(string $id, #[CurrentUser] ?User $user): JsonResponse
    {
        if (!$user) {
            return $this->json(ApiResponse::error('Требуется авторизация', 401), 401);
        }

        $command = new MarkReadCommand(
            conversationId: $id,
            userId: (string) $user->getId()->getValue(),
        );

        $this->commandBus->dispatch($command);

        return $this->json(ApiResponse::success(['message' => 'Отмечено как прочитанное']));
    }

    #[Route('/unread-count', name: 'unread_count', methods: ['GET'])]
    public function unreadCount(#[CurrentUser] ?User $user): JsonResponse
    {
        if (!$user) {
            return $this->json(ApiResponse::error('Требуется авторизация', 401), 401);
        }

        $count = $this->conversationRepository->countUnreadByUser(
            (string) $user->getId()->getValue()
        );

        return $this->json(ApiResponse::success(['unreadCount' => $count]));
    }
}
