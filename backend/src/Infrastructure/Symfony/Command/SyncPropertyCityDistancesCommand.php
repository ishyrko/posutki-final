<?php

declare(strict_types=1);

namespace App\Infrastructure\Symfony\Command;

use App\Domain\Property\Entity\Property;
use App\Infrastructure\Service\PropertyCityDistanceCalculator;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

#[AsCommand(
    name: 'app:sync-property-city-distances',
    description: 'Пересчитать расстояния до ближайшего города и областного центра для всех объявлений',
)]
final class SyncPropertyCityDistancesCommand extends Command
{
    public function __construct(
        private readonly EntityManagerInterface $em,
        private readonly PropertyCityDistanceCalculator $propertyCityDistanceCalculator,
    ) {
        parent::__construct();
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);

        $batchSize = 100;
        $offset = 0;
        $total = 0;

        do {
            /** @var list<Property> $properties */
            $properties = $this->em
                ->createQuery('SELECT p FROM App\Domain\Property\Entity\Property p ORDER BY p.id ASC')
                ->setFirstResult($offset)
                ->setMaxResults($batchSize)
                ->getResult();

            foreach ($properties as $property) {
                $this->propertyCityDistanceCalculator->syncForProperty($property);
                ++$total;
            }

            $this->em->flush();
            $this->em->clear();

            $offset += $batchSize;
        } while (count($properties) === $batchSize);

        $io->success(sprintf('Пересчитано объявлений: %d', $total));

        return Command::SUCCESS;
    }
}
