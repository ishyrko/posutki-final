<?php

declare(strict_types=1);

namespace App\Application\Query\Property\GetOwnerCalendar;

use App\Application\Service\PropertyCalendarAggregator;
use App\Domain\Property\Entity\PropertyAvailabilityBlock;
use App\Domain\Property\Repository\PropertyAvailabilityBlockRepositoryInterface;
use App\Domain\Property\Repository\PropertyRepositoryInterface;
use App\Domain\Shared\Exception\DomainException;
use App\Domain\Shared\ValueObject\Id;

final class GetOwnerCalendarHandler
{
    public function __construct(
        private readonly PropertyRepositoryInterface $propertyRepository,
        private readonly PropertyAvailabilityBlockRepositoryInterface $availabilityBlockRepository,
        private readonly PropertyCalendarAggregator $propertyCalendarAggregator,
    ) {
    }

    public function __invoke(GetOwnerCalendarQuery $query): array
    {
        $propertyId = Id::fromString($query->propertyId);
        $userId = Id::fromString($query->userId);

        $property = $this->propertyRepository->findById($propertyId);
        if ($property === null) {
            throw new DomainException('Объявление не найдено');
        }

        if (!$property->getOwnerId()->equals($userId)) {
            throw new DomainException('Нет прав на просмотр календаря этого объявления');
        }

        if ($property->getDealType() !== 'daily') {
            throw new DomainException('Календарь доступен только для посуточной аренды');
        }

        $token = $property->ensureCalendarExportToken();
        $this->propertyRepository->save($property);

        $blocks = $this->availabilityBlockRepository->findByPropertyId($propertyId);
        $importedData = $this->propertyCalendarAggregator->resolveImportedCalendarData($property);

        return [
            'propertyId' => (string) $propertyId->getValue(),
            'propertyTitle' => $property->getTitle(),
            'manualBlocks' => array_map(
                static fn(PropertyAvailabilityBlock $block): array => [
                    'id' => (string) $block->getId()->getValue(),
                    'start' => $block->getStartDate()->format('Y-m-d'),
                    'end' => $block->getEndDate()->format('Y-m-d'),
                    'note' => $block->getNote(),
                    'createdAt' => $block->getCreatedAt()->format('c'),
                ],
                $blocks,
            ),
            'importedBlockedRanges' => $importedData['blockedRanges'],
            'externalCalendarUrls' => $property->getExternalCalendarUrls() ?? [],
            'externalCalendarSyncedAt' => $property->getExternalCalendarSyncedAt()?->format('c'),
            'exportToken' => $token,
            'exportUrl' => $this->buildExportUrl($query->exportBaseUrl, $token),
        ];
    }

    private function buildExportUrl(?string $baseUrl, string $token): string
    {
        if ($baseUrl === null || $baseUrl === '') {
            return sprintf('/ical/%s.ics', $token);
        }

        return rtrim($baseUrl, '/') . sprintf('/ical/%s.ics', $token);
    }
}
