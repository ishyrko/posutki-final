<?php

declare(strict_types=1);

namespace App\Application\Command\Property\ArchiveProperty;

use App\Application\Service\FreeListingLimitService;
use App\Domain\Property\Repository\PropertyRepositoryInterface;
use App\Domain\Shared\Exception\DomainException;
use App\Domain\Shared\ValueObject\Id;

final class ArchivePropertyHandler
{
    public function __construct(
        private readonly PropertyRepositoryInterface $propertyRepository,
        private readonly FreeListingLimitService $freeListingLimitService,
    ) {
    }

    public function __invoke(ArchivePropertyCommand $command): string
    {
        $propertyId = Id::fromString($command->propertyId);
        $userId = Id::fromString($command->userId);

        $property = $this->propertyRepository->findById($propertyId);

        if ($property === null) {
            throw new DomainException('Объявление не найдено');
        }

        if (!$property->getOwnerId()->equals($userId)) {
            throw new DomainException('Нет прав на скрытие этого объявления');
        }

        if (!in_array($property->getStatus(), ['published', 'awaiting_payment'], true)) {
            throw new DomainException('Скрыть можно только опубликованное объявление или объявление, ожидающее оплаты');
        }

        $previousStatus = $property->getStatus();
        $property->archive();
        $this->propertyRepository->save($property);
        $this->freeListingLimitService->maybeRefreshCityLimitAfterStatusChange($property, $previousStatus);

        $archivedAt = $property->getArchivedAt();
        if ($archivedAt === null) {
            throw new DomainException('Не удалось заархивировать объявление');
        }

        return $archivedAt->format('c');
    }
}
