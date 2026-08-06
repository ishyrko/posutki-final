<?php

declare(strict_types=1);

namespace App\Infrastructure\Symfony\Command;

use App\Domain\Property\Entity\Property;
use App\Infrastructure\Service\LandmarkProximityCalculator;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

#[AsCommand(
    name: 'app:sync-landmark-proximity',
    description: 'Пересчитать близость ко всем достопримечательностям для всех объявлений',
)]
final class SyncLandmarkProximityCommand extends Command
{
    public function __construct(
        private readonly EntityManagerInterface $em,
        private readonly LandmarkProximityCalculator $landmarkProximityCalculator,
    ) {
        parent::__construct();
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);

        $batchSize = 100;
        $offset = 0;
        $total = 0;
        $withLandmarksCount = 0;

        do {
            /** @var list<Property> $properties */
            $properties = $this->em
                ->createQuery('SELECT p FROM App\Domain\Property\Entity\Property p ORDER BY p.id ASC')
                ->setFirstResult($offset)
                ->setMaxResults($batchSize)
                ->getResult();

            foreach ($properties as $property) {
                $this->landmarkProximityCalculator->syncForProperty($property);
                if ($this->hasNearbyLandmarks($property->getId()->getValue())) {
                    ++$withLandmarksCount;
                }
                ++$total;
            }

            $this->em->flush();
            $this->em->clear();

            $offset += $batchSize;
        } while (count($properties) === $batchSize);

        $io->success(sprintf(
            'Пересчитано объявлений: %d, из них у %d есть близость к достопримечательностям.',
            $total,
            $withLandmarksCount,
        ));

        return Command::SUCCESS;
    }

    private function hasNearbyLandmarks(int $propertyId): bool
    {
        $count = (int) $this->em
            ->createQuery('SELECT COUNT(pl.propertyId) FROM App\Domain\Property\Entity\PropertyLandmark pl WHERE pl.propertyId = :propertyId')
            ->setParameter('propertyId', $propertyId)
            ->getSingleScalarResult();

        return $count > 0;
    }
}
