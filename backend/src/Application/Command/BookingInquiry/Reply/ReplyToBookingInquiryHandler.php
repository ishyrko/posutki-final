<?php

declare(strict_types=1);

namespace App\Application\Command\BookingInquiry\Reply;

use App\Domain\BookingInquiry\Event\BookingInquiryRepliedEvent;
use App\Domain\BookingInquiry\Repository\BookingInquiryRepositoryInterface;
use App\Domain\Shared\Exception\DomainException;
use App\Domain\Shared\ValueObject\Id;
use App\Domain\User\Repository\UserRepositoryInterface;
use Symfony\Component\Messenger\MessageBusInterface;

final class ReplyToBookingInquiryHandler
{
    public function __construct(
        private readonly BookingInquiryRepositoryInterface $bookingInquiryRepository,
        private readonly UserRepositoryInterface $userRepository,
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
        if ($email !== null && trim($email) !== '') {
            return trim($email);
        }

        $userId = $inquiry->getUserId();
        if ($userId === null) {
            return null;
        }

        $user = $this->userRepository->findById($userId);
        if ($user === null) {
            return null;
        }

        $accountEmail = $user->getEmail()?->getValue();
        if ($accountEmail !== null && $user->isVerified()) {
            return $accountEmail;
        }

        return null;
    }
}
