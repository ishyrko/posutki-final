<?php

declare(strict_types=1);

namespace App\Application\Command\Property\CreateAvailabilityBlock;

use App\Domain\Property\Entity\PropertyAvailabilityBlock;
use App\Domain\Property\Repository\PropertyAvailabilityBlockRepositoryInterface;
use App\Domain\Property\Repository\PropertyRepositoryInterface;
use App\Domain\Shared\Exception\DomainException;
use App\Domain\Shared\ValueObject\Id;

final class CreateAvailabilityBlockHandler
{
    public function __construct(
        private readonly PropertyRepositoryInterface $propertyRepository,
        private readonly PropertyAvailabilityBlockRepositoryInterface $availabilityBlockRepository,
    ) {
    }

    public function __invoke(CreateAvailabilityBlockCommand $command): array
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

        $startDate = $this->parseDate($command->startDate, 'Дата начала');
        $endDate = $this->parseDate($command->endDate, 'Дата окончания');

        if ($endDate < $startDate) {
            throw new DomainException('Дата окончания не может быть раньше даты начала');
        }

        $block = new PropertyAvailabilityBlock(
            propertyId: $propertyId,
            startDate: $startDate,
            endDate: $endDate,
            note: $command->note,
        );

        $this->availabilityBlockRepository->save($block);

        return [
            'id' => (string) $block->getId()->getValue(),
            'start' => $block->getStartDate()->format('Y-m-d'),
            'end' => $block->getEndDate()->format('Y-m-d'),
            'note' => $block->getNote(),
            'createdAt' => $block->getCreatedAt()->format('c'),
        ];
    }

    private function parseDate(string $value, string $label): \DateTimeImmutable
    {
        $date = \DateTimeImmutable::createFromFormat('Y-m-d', trim($value));
        if ($date === false) {
            throw new DomainException(sprintf('%s указана в некорректном формате', $label));
        }

        return $date->setTime(0, 0);
    }
}
