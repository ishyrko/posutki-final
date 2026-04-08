<?php

declare(strict_types=1);

namespace App\Application\Command\Property\DeleteProperty;

use App\Domain\Property\Repository\PropertyRepositoryInterface;
use App\Domain\Shared\Exception\UnauthorizedException;
use App\Domain\Shared\ValueObject\Id;

readonly class DeletePropertyHandler
{
    public function __construct(
        private PropertyRepositoryInterface $propertyRepository,
    ) {
    }

    public function __invoke(DeletePropertyCommand $command): void
    {
        // Find property
        $property = $this->propertyRepository->findById(Id::fromString($command->propertyId));
        
        if (!$property) {
            throw new \InvalidArgumentException('Объявление не найдено');
        }

        // Check authorization
        if (!$property->isOwnedBy($command->userId)) {
            throw new UnauthorizedException('Нет прав на удаление этого объявления');
        }

        // Soft delete
        $property->delete();

        $this->propertyRepository->save($property);
    }
}