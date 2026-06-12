<?php

declare(strict_types=1);

namespace App\Application\Command\Property\DeleteAvailabilityBlock;

use App\Domain\Property\Repository\PropertyAvailabilityBlockRepositoryInterface;
use App\Domain\Property\Repository\PropertyRepositoryInterface;
use App\Domain\Shared\Exception\DomainException;
use App\Domain\Shared\ValueObject\Id;

final class DeleteAvailabilityBlockHandler
{
    public function __construct(
        private readonly PropertyRepositoryInterface $propertyRepository,
        private readonly PropertyAvailabilityBlockRepositoryInterface $availabilityBlockRepository,
    ) {
    }

    public function __invoke(DeleteAvailabilityBlockCommand $command): void
    {
        $propertyId = Id::fromString($command->propertyId);
        $userId = Id::fromString($command->userId);
        $blockId = Id::fromString($command->blockId);

        $property = $this->propertyRepository->findById($propertyId);
        if ($property === null) {
            throw new DomainException('Объявление не найдено');
        }

        if (!$property->getOwnerId()->equals($userId)) {
            throw new DomainException('Нет прав на управление календарём этого объявления');
        }

        $block = $this->availabilityBlockRepository->findById($blockId);
        if ($block === null || !$block->getPropertyId()->equals($propertyId)) {
            throw new DomainException('Блокировка не найдена');
        }

        $this->availabilityBlockRepository->delete($block);
    }
}
