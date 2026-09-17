<?php

declare(strict_types=1);

namespace App\Application\Query\Property\GetMyProperties;

use App\Domain\Property\Repository\PropertyRepositoryInterface;
use App\Domain\Review\Repository\ReviewRepositoryInterface;
use App\Domain\Shared\ValueObject\Id;

final class GetMyPropertiesSummaryHandler
{
    public function __construct(
        private readonly PropertyRepositoryInterface $propertyRepository,
        private readonly ReviewRepositoryInterface $reviewRepository,
    ) {
    }

    /**
     * @return array{hasAny: bool, awaitingPayment: int, unviewedReviews: int}
     */
    public function __invoke(GetMyPropertiesSummaryQuery $query): array
    {
        $ownerId = Id::fromString($query->userId);
        $statusCounts = $this->propertyRepository->countByOwnerGroupedByStatus($query->userId);
        $unviewedByProperty = $this->reviewRepository->countUnviewedGroupedByPropertyForOwner($ownerId);

        return [
            'hasAny' => array_sum($statusCounts) > 0,
            'awaitingPayment' => $statusCounts['awaiting_payment'] ?? 0,
            'unviewedReviews' => array_sum($unviewedByProperty),
        ];
    }
}
