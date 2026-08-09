<?php

declare(strict_types=1);

namespace App\Infrastructure\Symfony\Command;

use App\Domain\BookingInquiry\Repository\BookingInquiryRepositoryInterface;
use App\Domain\Favorite\Repository\FavoriteAddEventRepositoryInterface;
use App\Domain\Message\Repository\MessageRepositoryInterface;
use App\Domain\Property\Enum\PropertyType;
use App\Domain\Property\Repository\PropertyDailyStatRepositoryInterface;
use App\Domain\Property\Repository\PropertyRepositoryInterface;
use App\Domain\Shared\ValueObject\Id;
use App\Domain\User\Repository\UserRepositoryInterface;
use App\Infrastructure\Mail\PlacementMailer;
use App\Infrastructure\Service\FrontendUrlBuilder;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

#[AsCommand(
    name: 'app:notify-vip-expiring-soon',
    description: 'Email apartment owners whose VIP placement expires within 24 hours (cities with 20+ published apartments, engagement > 10 in last 14 days)',
)]
class NotifyVipExpiringSoonCommand extends Command
{
    /** Minimum published apartments in a city to send VIP expiry reminders. */
    private const MIN_PUBLISHED_APARTMENTS_IN_CITY = 20;

    /** Lookback for contact views / messages / inquiries / favorites shown in the email. */
    private const ENGAGEMENT_LOOKBACK_DAYS = 14;

    /** Send only when the engagement sum exceeds this threshold. */
    private const ENGAGEMENT_MIN_TOTAL = 10;

    public function __construct(
        private readonly PropertyRepositoryInterface $propertyRepository,
        private readonly UserRepositoryInterface $userRepository,
        private readonly PropertyDailyStatRepositoryInterface $propertyDailyStatRepository,
        private readonly MessageRepositoryInterface $messageRepository,
        private readonly BookingInquiryRepositoryInterface $bookingInquiryRepository,
        private readonly FavoriteAddEventRepositoryInterface $favoriteAddEventRepository,
        private readonly PlacementMailer $mailer,
        private readonly FrontendUrlBuilder $frontendUrls,
    ) {
        parent::__construct();
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);
        $now = new \DateTimeImmutable();
        $until = $now->modify('+24 hours');

        $properties = $this->propertyRepository->findWithPlacementLevelExpiringSoon(
            $now,
            $until,
            PropertyType::Apartment->value,
            self::MIN_PUBLISHED_APARTMENTS_IN_CITY,
        );
        $sent = 0;
        $skippedNoOwner = 0;
        $skippedLowEngagement = 0;

        foreach ($properties as $property) {
            $owner = $this->userRepository->findById($property->getOwnerId());
            if ($owner === null || $owner->getEmail()?->getValue() === null) {
                ++$skippedNoOwner;

                continue;
            }

            $recentEngagement = $this->resolveRecentEngagement($property->getId()->getValue());
            if ($recentEngagement === null) {
                $property->markPlacementLevelExpiryReminded($now);
                $this->propertyRepository->save($property);
                ++$skippedLowEngagement;

                continue;
            }

            $this->mailer->sendVipExpiringSoon(
                property: $property,
                owner: $owner,
                propertyUrl: $this->frontendUrls->publicPropertyForListing($property),
                listingsUrl: $this->frontendUrls->myListings(),
                dashboardUrl: $this->frontendUrls->cabinet(),
                recentEngagement: $recentEngagement,
            );

            $property->markPlacementLevelExpiryReminded($now);
            $this->propertyRepository->save($property);
            ++$sent;
        }

        $io->success(sprintf(
            'Sent %d VIP expiry reminder(s), skipped %d (no owner/email), skipped %d (engagement ≤ %d).',
            $sent,
            $skippedNoOwner,
            $skippedLowEngagement,
            self::ENGAGEMENT_MIN_TOTAL,
        ));

        return Command::SUCCESS;
    }

    /**
     * @return array{phoneViews: int, messages: int, bookingInquiries: int, favorites: int, total: int}|null
     */
    private function resolveRecentEngagement(int $propertyId): ?array
    {
        $days = self::ENGAGEMENT_LOOKBACK_DAYS;

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

        if ($total <= self::ENGAGEMENT_MIN_TOTAL) {
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
