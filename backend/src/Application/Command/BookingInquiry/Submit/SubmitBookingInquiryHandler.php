<?php

declare(strict_types=1);

namespace App\Application\Command\BookingInquiry\Submit;

use App\Domain\BookingInquiry\Entity\BookingInquiry;
use App\Domain\BookingInquiry\Event\BookingInquirySubmittedEvent;
use App\Domain\BookingInquiry\Repository\BookingInquiryRepositoryInterface;
use App\Application\Service\PropertyCalendarAggregator;
use App\Domain\Property\Entity\Property;
use App\Domain\Property\Repository\PropertyRepositoryInterface;
use App\Domain\Shared\Exception\DomainException;
use App\Domain\Shared\ValueObject\Id;
use App\Domain\User\Repository\UserRepositoryInterface;
use Symfony\Component\Messenger\MessageBusInterface;

final class SubmitBookingInquiryHandler
{
    public function __construct(
        private readonly BookingInquiryRepositoryInterface $bookingInquiryRepository,
        private readonly PropertyRepositoryInterface $propertyRepository,
        private readonly UserRepositoryInterface $userRepository,
        private readonly MessageBusInterface $notificationBus,
        private readonly PropertyCalendarAggregator $propertyCalendarAggregator,
    ) {
    }

    public function __invoke(SubmitBookingInquiryCommand $command): array
    {
        $property = $this->propertyRepository->findById(Id::fromString($command->propertyId));
        if ($property === null) {
            throw new DomainException('Объявление не найдено');
        }

        $ownerId = (string) $property->getOwnerId()->getValue();

        if ($command->userId !== null && $command->userId === $ownerId) {
            throw new DomainException('Нельзя отправить заявку на своё объявление');
        }

        $owner = $this->userRepository->findById(Id::fromString($ownerId));
        if ($owner === null || $owner->getEmail() === null || !$owner->isVerified()) {
            throw new DomainException('Заявка на бронирование для этого объявления недоступна');
        }

        if (!$owner->allowsMessagesAndInquiries()) {
            throw new DomainException('Владелец отключил приём сообщений и заявок на бронирование');
        }

        if ($command->userId === null && !$owner->allowsGuestBookingInquiries()) {
            throw new DomainException('Владелец принимает заявки только от зарегистрированных пользователей. Войдите в аккаунт.');
        }

        if ($command->userId === null && ($command->email === null || trim($command->email) === '')) {
            throw new DomainException('Укажите email — на него придёт ответ владельца');
        }

        $guests = $command->guests;
        if ($guests === null || $guests < 1) {
            throw new DomainException('Укажите количество гостей');
        }

        $maxGuests = $property->getMaxDailyGuests();
        if ($maxGuests !== null && $maxGuests > 0 && $guests > $maxGuests) {
            throw new DomainException(sprintf(
                'Максимум гостей для этого объявления — %d',
                $maxGuests,
            ));
        }

        if ($guests > 20) {
            throw new DomainException('Количество гостей не может быть больше 20');
        }

        $checkIn = $this->parseRequiredDate($command->checkIn, 'Укажите дату заезда');
        $checkOut = $this->parseRequiredDate($command->checkOut, 'Укажите дату выезда');

        if ($checkOut <= $checkIn) {
            throw new DomainException('Дата выезда должна быть позже даты заезда');
        }

        $nights = (int) $checkIn->diff($checkOut)->days;
        $minStayDays = max(1, $property->getMinStayDays() ?? 1);
        if ($nights < $minStayDays) {
            throw new DomainException($this->minStayMessage($minStayDays));
        }

        $this->assertDatesNotBlocked($property, $checkIn, $checkOut);

        $inquiry = new BookingInquiry(
            propertyId: Id::fromString($command->propertyId),
            ownerId: Id::fromString($ownerId),
            name: $command->name,
            phone: $command->phone,
            userId: $command->userId !== null ? Id::fromString($command->userId) : null,
            email: $command->email,
            guests: $guests,
            checkIn: $checkIn,
            checkOut: $checkOut,
            notes: $command->notes,
        );

        $this->bookingInquiryRepository->save($inquiry);

        $this->notificationBus->dispatch(new BookingInquirySubmittedEvent(
            (string) $inquiry->getId()->getValue(),
        ));

        return [
            'id' => (string) $inquiry->getId()->getValue(),
        ];
    }

    private function parseRequiredDate(?string $value, string $emptyMessage): \DateTimeImmutable
    {
        if ($value === null || trim($value) === '') {
            throw new DomainException($emptyMessage);
        }

        $date = \DateTimeImmutable::createFromFormat('!Y-m-d', trim($value));
        if ($date === false) {
            throw new DomainException('Некорректный формат даты');
        }

        return $date;
    }

    private function minStayMessage(int $minStayDays): string
    {
        $mod100 = $minStayDays % 100;
        $mod10 = $minStayDays % 10;
        if ($mod100 > 10 && $mod100 < 20) {
            $label = 'суток';
        } elseif ($mod10 > 1 && $mod10 < 5) {
            $label = 'суток';
        } elseif ($mod10 === 1) {
            $label = 'сутки';
        } else {
            $label = 'суток';
        }

        return sprintf('Минимальный срок проживания — %d %s', $minStayDays, $label);
    }

    private function assertDatesNotBlocked(
        Property $property,
        \DateTimeImmutable $checkIn,
        \DateTimeImmutable $checkOut,
    ): void {
        $calendarData = $this->propertyCalendarAggregator->getPublicCalendarData($property);
        $blockedKeys = $this->blockedDateKeys($calendarData['blockedRanges']);

        if (isset($blockedKeys[$checkIn->format('Y-m-d')])) {
            throw new DomainException('Дата заезда занята');
        }

        $cursor = $checkIn;
        while ($cursor < $checkOut) {
            if (isset($blockedKeys[$cursor->format('Y-m-d')])) {
                throw new DomainException('Выбранный период включает занятые даты');
            }

            $cursor = $cursor->modify('+1 day');
        }
    }

    /**
     * @param list<array{start: string, end: string}> $blockedRanges
     *
     * @return array<string, true>
     */
    private function blockedDateKeys(array $blockedRanges): array
    {
        $keys = [];

        foreach ($blockedRanges as $range) {
            $start = \DateTimeImmutable::createFromFormat('Y-m-d', $range['start']);
            $end = \DateTimeImmutable::createFromFormat('Y-m-d', $range['end']);

            if ($start === false || $end === false) {
                continue;
            }

            $cursor = $start;
            while ($cursor <= $end) {
                $keys[$cursor->format('Y-m-d')] = true;
                $cursor = $cursor->modify('+1 day');
            }
        }

        return $keys;
    }
}
