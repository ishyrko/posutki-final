<?php

declare(strict_types=1);

namespace App\Infrastructure\Service;

use App\Domain\BookingInquiry\Repository\BookingInquiryRepositoryInterface;
use App\Domain\Favorite\Repository\FavoriteAddEventRepositoryInterface;
use App\Domain\Message\Repository\MessageRepositoryInterface;
use App\Domain\Property\Repository\PropertyDailyStatRepositoryInterface;
use App\Domain\Shared\ValueObject\Id;

/**
 * Sums contact views, messages, booking inquiries and favorite adds for a property lookback window.
 */
final readonly class PropertyRecentEngagementResolver
{
    public const DEFAULT_LOOKBACK_DAYS = 14;

    public const DEFAULT_MIN_TOTAL = 10;

    public function __construct(
        private PropertyDailyStatRepositoryInterface $propertyDailyStatRepository,
        private MessageRepositoryInterface $messageRepository,
        private BookingInquiryRepositoryInterface $bookingInquiryRepository,
        private FavoriteAddEventRepositoryInterface $favoriteAddEventRepository,
    ) {
    }

    /**
     * @return array{phoneViews: int, messages: int, bookingInquiries: int, favorites: int, total: int}|null
     */
    public function resolveIfAboveThreshold(
        int $propertyId,
        int $days = self::DEFAULT_LOOKBACK_DAYS,
        int $minTotal = self::DEFAULT_MIN_TOTAL,
    ): ?array {
        $phoneViews = (int) array_sum(array_column(
            $this->propertyDailyStatRepository->findByPropertyAndPeriod($propertyId, $days),
            'phoneViews',
        ));
        $messages = (int) array_sum(array_column(
            $this->messageRepository->findDailyReceivedCountsByProperty($propertyId, $days),
            'count',
        ));
        $bookingInquiries = (int) array_sum(array_column(
            $this->bookingInquiryRepository->findDailyCountsByProperty($propertyId, $days),
            'count',
        ));
        $favorites = (int) array_sum(array_column(
            $this->favoriteAddEventRepository->findDailyCountsByProperty(Id::fromInt($propertyId), $days),
            'count',
        ));
        $total = $phoneViews + $messages + $bookingInquiries + $favorites;

        if ($total <= $minTotal) {
            return null;
        }

        return [
            'phoneViews' => $phoneViews,
            'messages' => $messages,
            'bookingInquiries' => $bookingInquiries,
            'favorites' => $favorites,
            'total' => $total,
        ];
    }
}
