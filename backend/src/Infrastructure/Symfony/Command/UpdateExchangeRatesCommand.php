<?php

declare(strict_types=1);

namespace App\Infrastructure\Symfony\Command;

use App\Infrastructure\Service\ExchangeRateService;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

#[AsCommand(
    name: 'app:update-exchange-rates',
    description: 'Fetch exchange rates from NBRB and recalculate property prices in BYN',
)]
class UpdateExchangeRatesCommand extends Command
{
    public function __construct(
        private readonly ExchangeRateService $exchangeRateService,
    ) {
        parent::__construct();
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);

        $io->section('Fetching exchange rates from NBRB...');
        $rates = $this->exchangeRateService->fetchAndUpdateRates();

        if (empty($rates)) {
            $io->error('Failed to fetch any exchange rates.');
            return Command::FAILURE;
        }

        foreach ($rates as $currency => $rate) {
            $io->writeln("  {$currency} = {$rate} BYN");
        }

        $io->section('Recalculating property prices...');
        $count = $this->exchangeRateService->recalculateAllPropertyPrices();
        $io->success("Updated {$count} properties.");

        return Command::SUCCESS;
    }
}
