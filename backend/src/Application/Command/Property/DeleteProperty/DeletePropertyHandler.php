<?php

declare(strict_types=1);

namespace App\Application\Command\Property\DeleteProperty;

use App\Domain\Property\Repository\PropertyRepositoryInterface;
use App\Domain\Shared\Exception\DomainException;
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

        if ($property->getPublishedAt() !== null) {
            if ($property->getStatus() !== 'archived') {
                throw new DomainException('Сначала скройте объявление перед удалением');
            }

            $archivedAt = $property->getArchivedAt();
            $threshold = new \DateTimeImmutable('-30 days');
            if ($archivedAt === null || $archivedAt > $threshold) {
                $daysLeft = $archivedAt !== null
                    ? (int) ceil((30 - (time() - $archivedAt->getTimestamp()) / 86400))
                    : 30;
                throw new DomainException("Удаление будет доступно через {$daysLeft} дн.");
            }
        }

        // Soft delete
        $property->delete();

        $this->propertyRepository->save($property);
    }
}