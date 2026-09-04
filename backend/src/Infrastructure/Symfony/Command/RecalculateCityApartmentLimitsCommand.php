<?php

declare(strict_types=1);

namespace App\Infrastructure\Symfony\Command;

use App\Application\Service\FreeListingLimitService;
use App\Domain\Property\Repository\CityRepositoryInterface;
use App\Domain\Property\Repository\PropertyRepositoryInterface;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

#[AsCommand(
    name: 'app:recalculate-city-apartment-limits',
    description: 'Recalculate cities.free_apartments_per_account from published apartment counts',
)]
class RecalculateCityApartmentLimitsCommand extends Command
{
    public function __construct(
        private readonly FreeListingLimitService $freeListingLimitService,
    ) {
        parent::__construct();
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);
        $updated = $this->freeListingLimitService->refreshAllCityApartmentLimits();

        $io->success(sprintf('Updated free_apartments_per_account for %d city/cities.', $updated));

        return Command::SUCCESS;
    }
}
