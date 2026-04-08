<?php

declare(strict_types=1);

namespace App\Application\Command\Property\BoostProperty;

use App\Domain\Property\Repository\PropertyRepositoryInterface;
use App\Domain\Shared\Exception\DomainException;
use App\Domain\Shared\ValueObject\Id;

final class BoostPropertyHandler
{
    public function __construct(
        private readonly PropertyRepositoryInterface $propertyRepository,
    ) {
    }

    public function __invoke(BoostPropertyCommand $command): string
    {
        $propertyId = Id::fromString($command->propertyId);
        $userId = Id::fromString($command->userId);

        $property = $this->propertyRepository->findById($propertyId);

        if ($property === null) {
            throw new DomainException('Объявление не найдено');
        }

        if (!$property->getOwnerId()->equals($userId)) {
            throw new DomainException('Нет прав на поднятие этого объявления');
        }

        $property->boost();
        $this->propertyRepository->save($property);

        return $property->getBoostedAt()->format('c');
    }
}
