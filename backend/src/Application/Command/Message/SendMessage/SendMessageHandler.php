<?php

declare(strict_types=1);

namespace App\Application\Command\Message\SendMessage;

use App\Domain\BookingInquiry\Repository\BookingInquiryRepositoryInterface;
use App\Domain\Message\Entity\Conversation;
use App\Domain\Message\Entity\Message;
use App\Domain\Message\Event\MessageSentEvent;
use App\Domain\Message\Repository\ConversationRepositoryInterface;
use App\Domain\Message\Repository\MessageRepositoryInterface;
use App\Domain\Property\Repository\PropertyRepositoryInterface;
use App\Domain\Shared\Exception\DomainException;
use App\Domain\Shared\ValueObject\Id;
use App\Domain\User\Repository\UserRepositoryInterface;
use Symfony\Component\Messenger\MessageBusInterface;

final class SendMessageHandler
{
    public function __construct(
        private readonly ConversationRepositoryInterface $conversationRepository,
        private readonly MessageRepositoryInterface $messageRepository,
        private readonly PropertyRepositoryInterface $propertyRepository,
        private readonly UserRepositoryInterface $userRepository,
        private readonly BookingInquiryRepositoryInterface $bookingInquiryRepository,
        private readonly MessageBusInterface $notificationBus,
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

            $sellerId = (string) $property->getOwnerId()->getValue();

            if ($command->buyerId !== null) {
                if ($sellerId !== $command->senderId) {
                    throw new DomainException('Только владелец объявления может начать переписку с гостем');
                }
                if ($command->buyerId === $command->senderId) {
                    throw new DomainException('Нельзя написать самому себе');
                }

                $buyer = $this->userRepository->findById(Id::fromString($command->buyerId));
                if ($buyer === null) {
                    throw new DomainException('Пользователь не найден');
                }

                $conversation = $this->conversationRepository->findByPropertyAndBuyer(
                    $command->propertyId,
                    $command->buyerId,
                );

                if ($conversation === null) {
                    $conversation = new Conversation(
                        propertyId: Id::fromString($command->propertyId),
                        sellerId: Id::fromString($sellerId),
                        buyerId: Id::fromString($command->buyerId),
                    );
                }
            } else {
                if ($sellerId === $command->senderId) {
                    throw new DomainException('Нельзя написать самому себе');
                }

                $conversation = $this->conversationRepository->findByPropertyAndBuyer(
                    $command->propertyId,
                    $command->senderId,
                );

                if ($conversation === null) {
                    $owner = $this->userRepository->findById(Id::fromString($sellerId));
                    if ($owner === null || !$owner->allowsMessagesAndInquiries()) {
                        throw new DomainException('Владелец отключил приём сообщений и заявок на бронирование');
                    }

                    $conversation = new Conversation(
                        propertyId: Id::fromString($command->propertyId),
                        sellerId: Id::fromString($sellerId),
                        buyerId: Id::fromString($command->senderId),
                    );
                }
            }
        }

        if ($command->bookingInquiryId !== null) {
            $this->linkBookingInquiryIfValid($conversation, $command->bookingInquiryId);
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

        $this->notificationBus->dispatch(new MessageSentEvent(
            conversationId: (string) $conversation->getId()->getValue(),
            senderId: $command->senderId,
            messageText: $command->text,
        ));

        return [
            'conversationId' => $conversation->getId()->getValue(),
            'messageId' => $message->getId()->getValue(),
        ];
    }

    private function linkBookingInquiryIfValid(Conversation $conversation, string $bookingInquiryId): void
    {
        if ($conversation->getBookingInquiryId() !== null) {
            return;
        }

        $inquiry = $this->bookingInquiryRepository->findById($bookingInquiryId);
        if ($inquiry === null) {
            throw new DomainException('Заявка не найдена');
        }

        if ((string) $inquiry->getPropertyId()->getValue() !== $conversation->getPropertyId()) {
            throw new DomainException('Заявка относится к другому объявлению');
        }

        if ($inquiry->getUserId() === null
            || (string) $inquiry->getUserId()->getValue() !== $conversation->getBuyerId()
        ) {
            throw new DomainException('Заявка не принадлежит этому гостю');
        }

        if ((string) $inquiry->getOwnerId()->getValue() !== $conversation->getSellerId()) {
            throw new DomainException('Заявка не относится к этой переписке');
        }

        $conversation->linkBookingInquiry($inquiry->getId());
    }
}
