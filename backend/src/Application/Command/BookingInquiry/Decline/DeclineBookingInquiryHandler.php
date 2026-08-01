<?php

declare(strict_types=1);

namespace App\Application\Command\BookingInquiry\Decline;

use App\Application\Command\CommandBusInterface;
use App\Application\Command\Property\DeleteAvailabilityBlock\DeleteAvailabilityBlockCommand;
use App\Domain\BookingInquiry\Repository\BookingInquiryRepositoryInterface;
use App\Domain\BookingInquiry\ValueObject\BookingInquiryStatus;
use App\Domain\Shared\Exception\DomainException;

final class DeclineBookingInquiryHandler
{
    public function __construct(
        private readonly BookingInquiryRepositoryInterface $bookingInquiryRepository,
        private readonly CommandBusInterface $commandBus,
    ) {
    }

    public function __invoke(DeclineBookingInquiryCommand $command): array
    {
        $inquiry = $this->bookingInquiryRepository->findById($command->inquiryId);
        if ($inquiry === null) {
            throw new DomainException('Заявка не найдена');
        }

        if ((string) $inquiry->getOwnerId()->getValue() !== $command->ownerId) {
            throw new DomainException('Нет прав на эту заявку');
        }

        if ($inquiry->getStatus() === BookingInquiryStatus::Declined) {
            throw new DomainException('Заявка уже отклонена');
        }

        $blockId = $inquiry->getAvailabilityBlockId();
        if ($blockId !== null) {
            $this->commandBus->dispatch(new DeleteAvailabilityBlockCommand(
                propertyId: (string) $inquiry->getPropertyId()->getValue(),
                userId: $command->ownerId,
                blockId: (string) $blockId->getValue(),
            ));
            $inquiry->detachAvailabilityBlock();
        }

        $inquiry->decline();
        $this->bookingInquiryRepository->save($inquiry);

        return [
            'id' => (string) $inquiry->getId()->getValue(),
            'status' => $inquiry->getStatus()->value,
        ];
    }
}
