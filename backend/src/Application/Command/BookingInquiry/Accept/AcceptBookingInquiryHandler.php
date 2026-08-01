<?php

declare(strict_types=1);

namespace App\Application\Command\BookingInquiry\Accept;

use App\Application\Command\CommandBusInterface;
use App\Application\Command\Property\CreateAvailabilityBlock\CreateAvailabilityBlockCommand;
use App\Application\Service\PropertyCalendarAggregator;
use App\Domain\BookingInquiry\Repository\BookingInquiryRepositoryInterface;
use App\Domain\BookingInquiry\ValueObject\BookingInquiryStatus;
use App\Domain\Property\Repository\PropertyRepositoryInterface;
use App\Domain\Shared\Exception\DomainException;
use App\Domain\Shared\ValueObject\Id;

final class AcceptBookingInquiryHandler
{
    public function __construct(
        private readonly BookingInquiryRepositoryInterface $bookingInquiryRepository,
        private readonly PropertyRepositoryInterface $propertyRepository,
        private readonly PropertyCalendarAggregator $propertyCalendarAggregator,
        private readonly CommandBusInterface $commandBus,
    ) {
    }

    public function __invoke(AcceptBookingInquiryCommand $command): array
    {
        $inquiry = $this->bookingInquiryRepository->findById($command->inquiryId);
        if ($inquiry === null) {
            throw new DomainException('Заявка не найдена');
        }

        if ((string) $inquiry->getOwnerId()->getValue() !== $command->ownerId) {
            throw new DomainException('Нет прав на эту заявку');
        }

        if ($inquiry->getStatus() === BookingInquiryStatus::Accepted) {
            throw new DomainException('Заявка уже принята');
        }

        $property = $this->propertyRepository->findById($inquiry->getPropertyId());
        if ($property === null) {
            throw new DomainException('Объявление не найдено');
        }

        if ($property->getDealType() !== 'daily') {
            throw new DomainException('Принять заявку можно только для посуточной аренды');
        }

        $checkIn = $inquiry->getCheckIn();
        if ($checkIn === null) {
            throw new DomainException('У заявки не указана дата заезда');
        }

        $blockEnd = $this->resolveBlockEndDate($checkIn, $inquiry->getCheckOut());
        $this->assertDatesAvailable($property, $checkIn, $blockEnd);

        $note = $this->buildBookedNote($inquiry->getName(), $inquiry->getNotes());
        $propertyId = (string) $inquiry->getPropertyId()->getValue();

        $blockResult = $this->commandBus->dispatch(new CreateAvailabilityBlockCommand(
            propertyId: $propertyId,
            userId: $command->ownerId,
            startDate: $checkIn->format('Y-m-d'),
            endDate: $blockEnd->format('Y-m-d'),
            note: $note,
        ));

        $inquiry->accept(Id::fromString((string) $blockResult['id']));
        $this->bookingInquiryRepository->save($inquiry);

        return [
            'id' => (string) $inquiry->getId()->getValue(),
            'status' => $inquiry->getStatus()->value,
            'availabilityBlockId' => (string) $blockResult['id'],
        ];
    }

    private function resolveBlockEndDate(
        \DateTimeImmutable $checkIn,
        ?\DateTimeImmutable $checkOut,
    ): \DateTimeImmutable {
        if ($checkOut === null || $checkOut <= $checkIn) {
            return $checkIn;
        }

        return $checkOut->modify('-1 day');
    }

    private function buildBookedNote(string $guestName, ?string $notes): string
    {
        $name = trim($guestName) !== '' ? trim($guestName) : 'Гость';
        $extra = trim($notes ?? '');
        $note = $extra !== '' ? "BOOKED:{$name}|{$extra}" : "BOOKED:{$name}";

        return mb_substr($note, 0, 255);
    }

    /**
     * @param \App\Domain\Property\Entity\Property $property
     */
    private function assertDatesAvailable($property, \DateTimeImmutable $start, \DateTimeImmutable $end): void
    {
        $calendarData = $this->propertyCalendarAggregator->getPublicCalendarData($property);
        $blockedKeys = $this->blockedDateKeys($calendarData['blockedRanges']);

        $cursor = $start;
        while ($cursor <= $end) {
            if (isset($blockedKeys[$cursor->format('Y-m-d')])) {
                throw new DomainException('Выбранные даты уже заняты в календаре');
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
            $rangeStart = \DateTimeImmutable::createFromFormat('Y-m-d', $range['start']);
            $rangeEnd = \DateTimeImmutable::createFromFormat('Y-m-d', $range['end']);

            if ($rangeStart === false || $rangeEnd === false) {
                continue;
            }

            $cursor = $rangeStart;
            while ($cursor <= $rangeEnd) {
                $keys[$cursor->format('Y-m-d')] = true;
                $cursor = $cursor->modify('+1 day');
            }
        }

        return $keys;
    }
}
