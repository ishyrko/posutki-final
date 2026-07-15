<?php

declare(strict_types=1);

namespace App\Infrastructure\Symfony\Command;

use App\Application\Service\PropertyPlacementService;
use App\Domain\Property\Repository\PropertyPlacementPurchaseRepositoryInterface;
use App\Domain\Property\Repository\PropertyRepositoryInterface;
use App\Domain\Shared\ValueObject\Id;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

#[AsCommand(
    name: 'app:expire-property-placements',
    description: 'Expire placement purchases/reservations and recompute property placement',
)]
class ExpirePropertyPlacementsCommand extends Command
{
    public function __construct(
        private readonly PropertyPlacementPurchaseRepositoryInterface $purchaseRepository,
        private readonly PropertyRepositoryInterface $propertyRepository,
        private readonly PropertyPlacementService $placementService,
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

        foreach (array_keys($propertyIds) as $propertyId) {
            $property = $this->propertyRepository->findById(Id::fromInt((int) $propertyId));
            if ($property !== null) {
                $this->placementService->recomputeForProperty($property, $now);
            }
        }

        $io->success(sprintf(
            'Expired %d active purchase(s), cancelled %d reservation(s), recomputed %d propert%s.',
            count($expiredActive),
            count($expiredReservations),
            count($propertyIds),
            count($propertyIds) === 1 ? 'y' : 'ies',
        ));

        return Command::SUCCESS;
    }
}
