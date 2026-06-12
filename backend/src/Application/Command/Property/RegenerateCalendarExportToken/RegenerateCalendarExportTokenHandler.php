<?php

declare(strict_types=1);

namespace App\Application\Command\Property\RegenerateCalendarExportToken;

use App\Domain\Property\Repository\PropertyRepositoryInterface;
use App\Domain\Shared\Exception\DomainException;
use App\Domain\Shared\ValueObject\Id;

final class RegenerateCalendarExportTokenHandler
{
    public function __construct(
        private readonly PropertyRepositoryInterface $propertyRepository,
    ) {
    }

    public function __invoke(RegenerateCalendarExportTokenCommand $command): string
    {
        $propertyId = Id::fromString($command->propertyId);
        $userId = Id::fromString($command->userId);

        $property = $this->propertyRepository->findById($propertyId);
        if ($property === null) {
            throw new DomainException('Объявление не найдено');
        }

        if (!$property->getOwnerId()->equals($userId)) {
            throw new DomainException('Нет прав на управление календарём этого объявления');
        }

        if ($property->getDealType() !== 'daily') {
            throw new DomainException('Календарь доступен только для посуточной аренды');
        }

        $token = $property->regenerateCalendarExportToken();
        $this->propertyRepository->save($property);

        return $token;
    }
}
