<?php

declare(strict_types=1);

namespace App\Infrastructure\Symfony\Command;

use App\Domain\Property\Repository\PropertyRepositoryInterface;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

#[AsCommand(
    name: 'app:deactivate-expired-properties',
    description: 'Archive published properties older than 2 months (by created_at)',
)]
class DeactivateExpiredPropertiesCommand extends Command
{
    public function __construct(
        private readonly PropertyRepositoryInterface $propertyRepository,
    ) {
        parent::__construct();
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);

        $threshold = new \DateTimeImmutable('-2 months');
        $properties = $this->propertyRepository->findPublishedOlderThan($threshold);

        $count = 0;
        foreach ($properties as $property) {
            $property->archive();
            $this->propertyRepository->save($property);
            ++$count;
        }

        $io->success("Deactivated {$count} expired propert" . ($count === 1 ? 'y' : 'ies') . '.');

        return Command::SUCCESS;
    }
}
