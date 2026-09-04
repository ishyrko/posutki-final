<?php

declare(strict_types=1);

namespace App\Application\Command\Property\UnarchiveProperty;

use App\Application\Service\FreeListingLimitService;
use App\Domain\Property\Repository\PropertyRepositoryInterface;
use App\Domain\Shared\Exception\DomainException;
use App\Domain\Shared\ValueObject\Id;

final class UnarchivePropertyHandler
{
    public function __construct(
        private readonly PropertyRepositoryInterface $propertyRepository,
        private readonly FreeListingLimitService $freeListingLimitService,
    ) {
    }

    /**
     * @return array{requiresPayment: bool, message: string}
     */
    public function __invoke(UnarchivePropertyCommand $command): array
    {
        $propertyId = Id::fromString($command->propertyId);
        $userId = Id::fromString($command->userId);

        $property = $this->propertyRepository->findById($propertyId);

        if ($property === null) {
            throw new DomainException('Объявление не найдено');
        }

        if (!$property->getOwnerId()->equals($userId)) {
            throw new DomainException('Нет прав на активацию этого объявления');
        }

        $previousStatus = $property->getStatus();
        $withinFreeLimit = $this->freeListingLimitService->canPublishFree($property);
        $property->unarchive($withinFreeLimit);
        $this->propertyRepository->save($property);
        $this->freeListingLimitService->maybeRefreshCityLimitAfterStatusChange($property, $previousStatus);

        return [
            'requiresPayment' => !$withinFreeLimit,
            'message' => $withinFreeLimit
                ? 'Объявление снова опубликовано'
                : $this->freeListingLimitService->buildAwaitingPaymentNotice($property),
        ];
    }
}
