<?php

declare(strict_types=1);

namespace App\Application\Command\Message\SendMessage;

use App\Domain\Message\Entity\Conversation;
use App\Domain\Message\Entity\Message;
use App\Domain\Message\Repository\ConversationRepositoryInterface;
use App\Domain\Message\Repository\MessageRepositoryInterface;
use App\Domain\Property\Repository\PropertyRepositoryInterface;
use App\Domain\Shared\Exception\DomainException;
use App\Domain\Shared\ValueObject\Id;

final class SendMessageHandler
{
    public function __construct(
        private readonly ConversationRepositoryInterface $conversationRepository,
        private readonly MessageRepositoryInterface $messageRepository,
        private readonly PropertyRepositoryInterface $propertyRepository,
    ) {
    }

    public function __invoke(SendMessageCommand $command): array
    {
        $conversation = null;

        if ($command->conversationId !== null) {
            $conversation = $this->conversationRepository->findById(
                Id::fromString($command->conversationId)
            );
            if ($conversation === null) {
                throw new DomainException('Переписка не найдена');
            }
            if (!$conversation->isParticipant($command->senderId)) {
                throw new DomainException('Доступ запрещён');
            }
        } else {
            $property = $this->propertyRepository->findById(
                Id::fromString($command->propertyId)
            );
            if ($property === null) {
                throw new DomainException('Объявление не найдено');
            }

            $sellerId = $property->getOwnerId()->getValue();

            if ((string) $sellerId === $command->senderId) {
                throw new DomainException('Нельзя написать самому себе');
            }

            $conversation = $this->conversationRepository->findByPropertyAndBuyer(
                $command->propertyId,
                $command->senderId,
            );

            if ($conversation === null) {
                $conversation = new Conversation(
                    propertyId: Id::fromString($command->propertyId),
                    sellerId: Id::fromInt($sellerId),
                    buyerId: Id::fromString($command->senderId),
                );
            }
        }

        $this->conversationRepository->save($conversation);

        $message = new Message(
            conversationId: $conversation->getId(),
            senderId: Id::fromString($command->senderId),
            text: $command->text,
        );

        $conversation->addMessage($command->text, $command->senderId);

        $this->conversationRepository->save($conversation);
        $this->messageRepository->save($message);

        return [
            'conversationId' => $conversation->getId()->getValue(),
            'messageId' => $message->getId()->getValue(),
        ];
    }
}
