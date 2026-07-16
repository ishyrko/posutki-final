<?php

declare(strict_types=1);

namespace App\Application\Notification\Placement;

use App\Domain\Property\Entity\PropertyPlacementPurchase;
use App\Domain\Property\Enum\PlacementPurchaseKind;
use App\Domain\Property\Event\PlacementPaymentSucceededEvent;
use App\Domain\Property\Repository\PropertyPlacementPurchaseRepositoryInterface;
use App\Domain\Property\Repository\PropertyRepositoryInterface;
use App\Domain\Shared\ValueObject\Id;
use App\Domain\User\Repository\UserRepositoryInterface;
use App\Infrastructure\Mail\PlacementMailer;
use App\Infrastructure\Service\FrontendUrlBuilder;

final readonly class PlacementPaymentSucceededHandler
{
    public function __construct(
        private PropertyPlacementPurchaseRepositoryInterface $purchaseRepository,
        private PropertyRepositoryInterface $propertyRepository,
        private UserRepositoryInterface $userRepository,
        private PlacementMailer $mailer,
        private FrontendUrlBuilder $frontendUrls,
    ) {
    }

    public function __invoke(PlacementPaymentSucceededEvent $event): void
    {
        $purchase = $this->purchaseRepository->findById($event->purchaseId);
        if (!$purchase instanceof PropertyPlacementPurchase) {
            return;
        }

        $property = $this->propertyRepository->findById(Id::fromInt($purchase->getPropertyId()));
        if ($property === null) {
            return;
        }

        $owner = $this->userRepository->findById($purchase->getOwnerId());
        if ($owner === null) {
            return;
        }

        $kindLabel = PlacementPurchaseKind::tryFrom($purchase->getKind())?->label() ?? $purchase->getKind();

        $this->mailer->sendPaymentSucceeded(
            purchase: $purchase,
            property: $property,
            owner: $owner,
            kindLabel: $kindLabel,
            propertyUrl: $this->frontendUrls->publicPropertyForListing($property),
            paymentUrl: $this->frontendUrls->placementPayment($event->purchaseId),
            dashboardUrl: $this->frontendUrls->cabinet(),
        );
    }
}
