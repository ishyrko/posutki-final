<?php

declare(strict_types=1);

namespace App\Application\Command\BookingInquiry\Reply;

use App\Domain\BookingInquiry\Event\BookingInquiryRepliedEvent;
use App\Domain\BookingInquiry\Repository\BookingInquiryRepositoryInterface;
use App\Domain\Shared\Exception\DomainException;
use Symfony\Component\Messenger\MessageBusInterface;

final class ReplyToBookingInquiryHandler
{
    public function __construct(
        private readonly BookingInquiryRepositoryInterface $bookingInquiryRepository,
        private readonly MessageBusInterface $notificationBus,
    ) {
    }

    public function __invoke(ReplyToBookingInquiryCommand $command): array
    {
        $inquiry = $this->bookingInquiryRepository->findById($command->inquiryId);
        if ($inquiry === null) {
            throw new DomainException('Заявка не найдена');
        }

        if ((string) $inquiry->getOwnerId()->getValue() !== $command->ownerId) {
            throw new DomainException('Нет прав на эту заявку');
        }

        if ($inquiry->getUserId() !== null) {
            throw new DomainException('Для зарегистрированных гостей используйте диалоги');
        }

        $text = trim($command->text);
        if ($text === '') {
            throw new DomainException('Укажите текст ответа');
        }

        $guestEmail = $this->resolveGuestEmail($inquiry);
        if ($guestEmail === null) {
            throw new DomainException('У гостя не указан email — свяжитесь по телефону');
        }

        $inquiry->reply($text);
        $this->bookingInquiryRepository->save($inquiry);

        $this->notificationBus->dispatch(new BookingInquiryRepliedEvent(
            (string) $inquiry->getId()->getValue(),
            $guestEmail,
        ));

        return [
            'id' => (string) $inquiry->getId()->getValue(),
            'status' => $inquiry->getStatus()->value,
            'ownerReply' => $inquiry->getOwnerReply(),
            'repliedAt' => $inquiry->getRepliedAt()?->format(\DateTimeInterface::ATOM),
        ];
    }

    private function resolveGuestEmail(\App\Domain\BookingInquiry\Entity\BookingInquiry $inquiry): ?string
    {
        $email = $inquiry->getEmail();
        if ($email === null || trim($email) === '') {
            return null;
        }

        return trim($email);
    }
}
