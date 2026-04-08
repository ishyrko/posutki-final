<?php

declare(strict_types=1);

namespace App\Application\Query\Message\GetMessages;

use App\Application\DTO\MessageDTO;
use App\Domain\Message\Repository\ConversationRepositoryInterface;
use App\Domain\Message\Repository\MessageRepositoryInterface;
use App\Domain\Shared\Exception\DomainException;
use App\Domain\Shared\ValueObject\Id;

final class GetMessagesHandler
{
    public function __construct(
        private readonly ConversationRepositoryInterface $conversationRepository,
        private readonly MessageRepositoryInterface $messageRepository,
    ) {
    }

    public function __invoke(GetMessagesQuery $query): array
    {
        $conversation = $this->conversationRepository->findById(
            Id::fromString($query->conversationId)
        );

        if ($conversation === null) {
            throw new DomainException('Переписка не найдена');
        }

        if (!$conversation->isParticipant($query->userId)) {
            throw new DomainException('Доступ запрещён');
        }

        $messages = $this->messageRepository->findByConversation(
            $query->conversationId,
            $query->page,
            $query->limit,
        );
        $total = $this->messageRepository->countByConversation($query->conversationId);

        return [
            'items' => array_map(
                fn($m) => MessageDTO::fromEntity($m),
                $messages
            ),
            'total' => $total,
            'page' => $query->page,
            'limit' => $query->limit,
        ];
    }
}
