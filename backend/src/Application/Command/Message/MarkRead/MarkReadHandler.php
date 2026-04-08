<?php

declare(strict_types=1);

namespace App\Application\Command\Message\MarkRead;

use App\Domain\Message\Repository\ConversationRepositoryInterface;
use App\Domain\Message\Repository\MessageRepositoryInterface;
use App\Domain\Shared\Exception\DomainException;
use App\Domain\Shared\ValueObject\Id;

final class MarkReadHandler
{
    public function __construct(
        private readonly ConversationRepositoryInterface $conversationRepository,
        private readonly MessageRepositoryInterface $messageRepository,
    ) {
    }

    public function __invoke(MarkReadCommand $command): void
    {
        $conversation = $this->conversationRepository->findById(
            Id::fromString($command->conversationId)
        );

        if ($conversation === null) {
            throw new DomainException('Переписка не найдена');
        }

        if (!$conversation->isParticipant($command->userId)) {
            throw new DomainException('Доступ запрещён');
        }

        $conversation->markReadBy($command->userId);
        $this->conversationRepository->save($conversation);

        $this->messageRepository->markAllReadInConversation(
            $command->conversationId,
            $command->userId,
        );
    }
}
