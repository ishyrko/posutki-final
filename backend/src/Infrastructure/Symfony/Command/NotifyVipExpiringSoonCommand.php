<?php

declare(strict_types=1);

namespace App\Infrastructure\Symfony\Command;

use App\Application\Service\FreeListingLimitService;
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
    description: 'Email owners whose paid VIP placement expires within 72 hours; trial VIP keeps legacy filters',
)]
class NotifyVipExpiringSoonCommand extends Command
{
    private const MIN_PUBLISHED_APARTMENTS_IN_CITY = 20;

    public function __construct(
        private readonly PropertyRepositoryInterface $propertyRepository,
        private readonly UserRepositoryInterface $userRepository,
        private readonly PropertyRecentEngagementResolver $engagementResolver,
        private readonly FreeListingLimitService $freeListingLimitService,
        private readonly PlacementMailer $mailer,
        private readonly FrontendUrlBuilder $frontendUrls,
    ) {
        parent::__construct();
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);
        $now = new \DateTimeImmutable();
        $until = $now->modify('+72 hours');

        $properties = $this->propertyRepository->findWithPlacementLevelExpiringSoon($now, $until);
        $sent = 0;
        $skippedNoOwner = 0;
        $skippedTrialFilters = 0;
        $publishedApartmentCountByCity = [];

        foreach ($properties as $property) {
            $owner = $this->userRepository->findById($property->getOwnerId());
            if ($owner === null || $owner->getEmail()?->getValue() === null) {
                ++$skippedNoOwner;

                continue;
            }

            if ($property->isPlacementIsTrial()) {
                if (!$this->passesTrialReminderFilters($property, $publishedApartmentCountByCity)) {
                    ++$skippedTrialFilters;

                    continue;
                }

                $recentEngagement = $this->engagementResolver->resolveIfAboveThreshold(
                    $property->getId()->getValue(),
                );
                if ($recentEngagement === null) {
                    $property->markPlacementLevelExpiryReminded($now);
                    $this->propertyRepository->save($property);
                    ++$skippedTrialFilters;

                    continue;
                }

                $this->mailer->sendVipExpiringSoon(
                    property: $property,
                    owner: $owner,
                    propertyUrl: $this->frontendUrls->publicPropertyForListing($property),
                    listingsUrl: $this->frontendUrls->myListings(),
                    dashboardUrl: $this->frontendUrls->cabinet(),
                    recentEngagement: $recentEngagement,
                    freeSlotUnavailable: false,
                );
            } else {
                $recentEngagement = $this->engagementResolver->resolveIfAboveThreshold(
                    $property->getId()->getValue(),
                );
                $freeSlotUnavailable = !$this->freeListingLimitService->canPublishFree($property);

                $this->mailer->sendVipExpiringSoon(
                    property: $property,
                    owner: $owner,
                    propertyUrl: $this->frontendUrls->publicPropertyForListing($property),
                    listingsUrl: $this->frontendUrls->myListings(),
                    dashboardUrl: $this->frontendUrls->cabinet(),
                    recentEngagement: $recentEngagement,
                    freeSlotUnavailable: $freeSlotUnavailable,
                );
            }

            $property->markPlacementLevelExpiryReminded($now);
            $this->propertyRepository->save($property);
            ++$sent;
        }

        $io->success(sprintf(
            'Sent %d VIP expiry reminder(s), skipped %d (no owner/email), skipped %d (trial filters / low engagement).',
            $sent,
            $skippedNoOwner,
            $skippedTrialFilters,
        ));

        return Command::SUCCESS;
    }

    /**
     * @param array<int, int> $publishedApartmentCountByCity
     */
    private function passesTrialReminderFilters(Property $property, array &$publishedApartmentCountByCity): bool
    {
        if ($property->getType() !== PropertyType::Apartment->value) {
            return false;
        }

        $cityId = $property->getCityId();
        if (!isset($publishedApartmentCountByCity[$cityId])) {
            $publishedApartmentCountByCity[$cityId] = array_sum(
                $this->propertyRepository->countPublishedByEffectiveLevel(
                    PropertyType::Apartment->value,
                    $cityId,
                ),
            );
        }

        return $publishedApartmentCountByCity[$cityId] >= self::MIN_PUBLISHED_APARTMENTS_IN_CITY;
    }
}
