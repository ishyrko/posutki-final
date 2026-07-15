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
    name: 'app:reshuffle-placement',
    description: 'Reshuffle placementShuffleKey for special and standard published properties',
)]
class ReshufflePlacementCommand extends Command
{
    public function __construct(
        private readonly PropertyRepositoryInterface $propertyRepository,
    ) {
        parent::__construct();
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);
        $properties = $this->propertyRepository->findPublishedForReshuffle();
        $count = 0;

        foreach ($properties as $property) {
            $property->reshufflePlacement();
            $this->propertyRepository->save($property);
            ++$count;
        }

        $io->success(sprintf('Reshuffled %d propert%s.', $count, $count === 1 ? 'y' : 'ies'));

        return Command::SUCCESS;
    }
}
