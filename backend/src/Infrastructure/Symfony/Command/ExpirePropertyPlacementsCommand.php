<?php

declare(strict_types=1);

namespace App\Infrastructure\Symfony\Command;

use App\Application\Service\PropertyPlacementService;
use App\Domain\Property\Entity\Property;
use App\Domain\Property\Enum\PropertyType;
use App\Domain\Property\Repository\PropertyPlacementPurchaseRepositoryInterface;
use App\Domain\Property\Repository\PropertyRepositoryInterface;
use App\Domain\Shared\ValueObject\Id;
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
    name: 'app:expire-property-placements',
    description: 'Expire placement purchases/reservations, recompute property placement, email owners when VIP ends',
)]
class ExpirePropertyPlacementsCommand extends Command
{
    private const MIN_PUBLISHED_APARTMENTS_IN_CITY = 20;

    public function __construct(
        private readonly PropertyPlacementPurchaseRepositoryInterface $purchaseRepository,
        private readonly PropertyRepositoryInterface $propertyRepository,
        private readonly PropertyPlacementService $placementService,
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
        $propertyIds = [];

        $expiredActive = $this->purchaseRepository->findExpiredActive($now);
        foreach ($expiredActive as $purchase) {
            $purchase->markExpired();
            $this->purchaseRepository->save($purchase);
            $propertyIds[$purchase->getPropertyId()] = true;
        }

        $expiredReservations = $this->purchaseRepository->findExpiredReservations($now);
        foreach ($expiredReservations as $purchase) {
            $purchase->cancelReservation();
            $this->purchaseRepository->save($purchase);
            $propertyIds[$purchase->getPropertyId()] = true;
        }

        foreach ($this->propertyRepository->findWithExpiredPlacement($now) as $property) {
            $propertyIds[$property->getId()->getValue()] = true;
        }

        $emailsSent = 0;
        $publishedApartmentCountByCity = [];

        foreach (array_keys($propertyIds) as $propertyId) {
            $property = $this->propertyRepository->findById(Id::fromInt((int) $propertyId));
            if ($property === null) {
                continue;
            }

            if ($this->shouldNotifyVipExpired($property, $now, $publishedApartmentCountByCity)) {
                if ($this->notifyVipExpired($property)) {
                    ++$emailsSent;
                    sleep(2);
                }
            }

            $this->placementService->recomputeForProperty($property, $now);
        }

        $io->success(sprintf(
            'Expired %d active purchase(s), cancelled %d reservation(s), recomputed %d propert%s, sent %d VIP-expired email(s).',
            count($expiredActive),
            count($expiredReservations),
            count($propertyIds),
            count($propertyIds) === 1 ? 'y' : 'ies',
            $emailsSent,
        ));

        return Command::SUCCESS;
    }

    /**
     * @param array<int, int> $publishedApartmentCountByCity
     */
    private function shouldNotifyVipExpired(
        Property $property,
        \DateTimeImmutable $now,
        array &$publishedApartmentCountByCity,
    ): bool {
        if ($property->getStatus() !== 'published') {
            return false;
        }
        if ($property->getType() !== PropertyType::Apartment->value) {
            return false;
        }
        if ($property->getPlacementBaseLevel() <= 0) {
            return false;
        }

        $expiresAt = $property->getPlacementLevelExpiresAt();
        if ($expiresAt === null || $expiresAt > $now) {
            return false;
        }

        // Still gets free VIP 1 trial after paid VIP ends — not a drop to free.
        $freeTrialEndsAt = $property->getFreeTrialEndsAt();
        if ($freeTrialEndsAt !== null && $freeTrialEndsAt > $now) {
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

    private function notifyVipExpired(Property $property): bool
    {
        $owner = $this->userRepository->findById($property->getOwnerId());
        if ($owner === null || $owner->getEmail()?->getValue() === null) {
            return false;
        }

        $recentEngagement = $this->engagementResolver->resolveIfAboveThreshold(
            $property->getId()->getValue(),
        );
        if ($recentEngagement === null) {
            return false;
        }

        $this->mailer->sendVipExpired(
            property: $property,
            owner: $owner,
            level: $property->getPlacementBaseLevel(),
            isTrial: $property->isPlacementIsTrial(),
            expiresAt: $property->getPlacementLevelExpiresAt(),
            propertyUrl: $this->frontendUrls->publicPropertyForListing($property),
            listingsUrl: $this->frontendUrls->myListings(),
            dashboardUrl: $this->frontendUrls->cabinet(),
            recentEngagement: $recentEngagement,
        );

        return true;
    }
}
