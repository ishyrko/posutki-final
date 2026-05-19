<?php

declare(strict_types=1);

namespace App\Infrastructure\Symfony\Command;

use App\Infrastructure\Service\MarketplaceDataPurger;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

#[AsCommand(
    name: 'app:purge-marketplace-data',
    description: 'Remove all users and property listings from the database (keeps regions, cities, articles, etc.)',
)]
final class PurgeMarketplaceDataCommand extends Command
{
    public function __construct(
        private readonly EntityManagerInterface $em,
        private readonly MarketplaceDataPurger $purger,
    ) {
        parent::__construct();
    }

    protected function configure(): void
    {
        $this->addOption(
            'force',
            'f',
            InputOption::VALUE_NONE,
            'Run without confirmation',
        );
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);

        if (!$input->getOption('force')) {
            $confirmed = $io->confirm(
                'Удалить всех пользователей и объявления? Справочники адресов, статьи и статические страницы останутся.',
                false,
            );
            if (!$confirmed) {
                $io->warning('Отменено.');

                return Command::SUCCESS;
            }
        }

        $conn = $this->em->getConnection();
        $this->purger->purgeUsersAndProperties($conn);
        $this->em->clear();

        $io->success(
            'Пользователи и объявления удалены. Создайте админа: make admin-user EMAIL=... PASSWORD=...',
        );

        return Command::SUCCESS;
    }
}
