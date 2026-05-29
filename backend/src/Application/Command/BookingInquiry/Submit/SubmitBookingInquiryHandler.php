<?php

declare(strict_types=1);

namespace App\Application\Command\BookingInquiry\Submit;

use App\Domain\BookingInquiry\Entity\BookingInquiry;
use App\Domain\BookingInquiry\Event\BookingInquirySubmittedEvent;
use App\Domain\BookingInquiry\Repository\BookingInquiryRepositoryInterface;
use App\Application\Service\IcsCalendarService;
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
        private readonly IcsCalendarService $icsCalendarService,
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

        $checkIn = $this->parseDate($command->checkIn);
        $checkOut = $this->parseDate($command->checkOut);

        if ($checkIn !== null && $checkOut !== null && $checkOut < $checkIn) {
            throw new DomainException('Дата выезда не может быть раньше даты заезда');
        }

        $this->assertDatesNotBlocked($property->getExternalCalendarUrls(), $checkIn, $checkOut);

        $inquiry = new BookingInquiry(
            propertyId: Id::fromString($command->propertyId),
            ownerId: Id::fromString($ownerId),
            name: $command->name,
            phone: $command->phone,
            userId: $command->userId !== null ? Id::fromString($command->userId) : null,
            email: $command->email,
            guests: $command->guests,
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

    private function parseDate(?string $value): ?\DateTimeImmutable
    {
        if ($value === null || trim($value) === '') {
            return null;
        }

        $date = \DateTimeImmutable::createFromFormat('Y-m-d', trim($value));
        if ($date === false) {
            throw new DomainException('Некорректный формат даты');
        }

        return $date;
    }

    /**
     * @param list<string>|null $externalCalendarUrls
     */
    private function assertDatesNotBlocked(
        ?array $externalCalendarUrls,
        ?\DateTimeImmutable $checkIn,
        ?\DateTimeImmutable $checkOut,
    ): void {
        if ($externalCalendarUrls === null || $externalCalendarUrls === []) {
            return;
        }

        if ($checkIn === null && $checkOut === null) {
            return;
        }

        $calendarData = $this->icsCalendarService->fetchCalendarData($externalCalendarUrls);
        $blockedKeys = $this->blockedDateKeys($calendarData['blockedRanges']);

        if ($checkIn !== null && isset($blockedKeys[$checkIn->format('Y-m-d')])) {
            throw new DomainException('Дата заезда занята');
        }

        if ($checkIn === null || $checkOut === null) {
            return;
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
