<?php

declare(strict_types=1);

namespace App\Infrastructure\Symfony\Command;

use App\Domain\Property\Enum\PropertyType;
use App\Domain\Property\Repository\PropertyRepositoryInterface;
use App\Domain\User\Repository\UserRepositoryInterface;
use App\Infrastructure\Mail\PlacementMailer;
use App\Infrastructure\Service\FrontendUrlBuilder;
use App\Infrastructure\Service\PropertyRecentEngagementResolver;
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

    public function __construct(
        private readonly PropertyRepositoryInterface $propertyRepository,
        private readonly UserRepositoryInterface $userRepository,
        private readonly PropertyRecentEngagementResolver $engagementResolver,
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

            $recentEngagement = $this->engagementResolver->resolveIfAboveThreshold(
                $property->getId()->getValue(),
            );
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
            PropertyRecentEngagementResolver::DEFAULT_MIN_TOTAL,
        ));

        return Command::SUCCESS;
    }
}
