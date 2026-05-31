<?php

declare(strict_types=1);

namespace App\Infrastructure\Symfony\Command;

use App\Domain\Property\Entity\Property;
use App\Infrastructure\Service\MetroProximityCalculator;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

#[AsCommand(
    name: 'app:sync-metro-proximity',
    description: 'Пересчитать близость ко всем станциям метро для всех объявлений',
)]
final class SyncMetroProximityCommand extends Command
{
    public function __construct(
        private readonly EntityManagerInterface $em,
        private readonly MetroProximityCalculator $metroProximityCalculator,
    ) {
        parent::__construct();
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);

        $batchSize = 100;
        $offset = 0;
        $total = 0;
        $nearMetroCount = 0;

        do {
            /** @var list<Property> $properties */
            $properties = $this->em
                ->createQuery('SELECT p FROM App\Domain\Property\Entity\Property p ORDER BY p.id ASC')
                ->setFirstResult($offset)
                ->setMaxResults($batchSize)
                ->getResult();

            foreach ($properties as $property) {
                $this->metroProximityCalculator->syncForProperty($property);
                if ($property->isNearMetro()) {
                    ++$nearMetroCount;
                }
                ++$total;
            }

            $this->em->flush();
            $this->em->clear();

            $offset += $batchSize;
        } while (count($properties) === $batchSize);

        $io->success(sprintf(
            'Пересчитано объявлений: %d, из них у %d указана близость к метро.',
            $total,
            $nearMetroCount,
        ));

        return Command::SUCCESS;
    }
}
