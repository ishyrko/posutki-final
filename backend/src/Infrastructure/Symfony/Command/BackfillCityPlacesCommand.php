<?php

declare(strict_types=1);

namespace App\Infrastructure\Symfony\Command;

use App\Domain\Property\Entity\Property;
use App\Domain\Property\Repository\CityRepositoryInterface;
use App\Domain\Property\Service\CityMicrodistrictResolverInterface;
use App\Domain\Property\Service\ResidentialComplexResolverInterface;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

#[AsCommand(
    name: 'app:backfill-city-places',
    description: 'Fill city microdistrict and residential complex for apartment listings',
)]
final class BackfillCityPlacesCommand extends Command
{
    public function __construct(
        private readonly EntityManagerInterface $em,
        private readonly CityRepositoryInterface $cityRepository,
        private readonly CityMicrodistrictResolverInterface $microdistrictResolver,
        private readonly ResidentialComplexResolverInterface $residentialComplexResolver,
    ) {
        parent::__construct();
    }

    protected function configure(): void
    {
        $this
            ->addOption('city', null, InputOption::VALUE_REQUIRED, 'Process only apartments in city slug')
            ->addOption('force', null, InputOption::VALUE_NONE, 'Recompute for all apartments and refresh geocoder cache');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);
        $force = (bool) $input->getOption('force');

        $cityIds = [];
        $citySlugFilter = $input->getOption('city');
        if (is_string($citySlugFilter) && $citySlugFilter !== '') {
            $city = $this->cityRepository->findBySlug($citySlugFilter);
            if ($city === null) {
                $io->error(sprintf('City «%s» not found.', $citySlugFilter));

                return Command::FAILURE;
            }
            $cityIds[] = $city->getId();
            $io->note(sprintf('Filter by city: %s', $citySlugFilter));
        }

        $dql = 'SELECT p.id FROM App\Domain\Property\Entity\Property p WHERE p.type = :type';
        if ($cityIds !== []) {
            $dql .= ' AND p.cityId IN (:cityIds)';
        }
        if (!$force) {
            $dql .= ' AND (p.cityMicrodistrictId IS NULL OR p.residentialComplexId IS NULL)';
        }
        $dql .= ' ORDER BY p.id ASC';

        $query = $this->em->createQuery($dql)->setParameter('type', 'apartment');
        if ($cityIds !== []) {
            $query->setParameter('cityIds', $cityIds);
        }

        /** @var list<int> $propertyIds */
        $propertyIds = $query->getSingleColumnResult();

        $processed = 0;
        $linkedMicro = 0;
        $linkedComplex = 0;

        foreach ($propertyIds as $propertyId) {
            /** @var Property|null $property */
            $property = $this->em->find(Property::class, $propertyId);
            if ($property === null) {
                continue;
            }

            ++$processed;
            $coordinates = $property->getCoordinates();
            $propertyIdValue = $property->getId()->getValue();

            $micro = $this->microdistrictResolver->resolve(
                $coordinates->getLatitude(),
                $coordinates->getLongitude(),
                $property->getCityId(),
                $propertyIdValue,
                $force,
            );
            if ($micro !== null) {
                $property->setCityMicrodistrictId($micro->getId());
                ++$linkedMicro;
            } elseif ($force) {
                $property->setCityMicrodistrictId(null);
            }

            $complex = $this->residentialComplexResolver->resolve(
                $coordinates->getLatitude(),
                $coordinates->getLongitude(),
                $property->getCityId(),
                $propertyIdValue,
                $force,
            );
            if ($complex !== null) {
                $property->setResidentialComplexId($complex->getId());
                ++$linkedComplex;
            } elseif ($force) {
                $property->setResidentialComplexId(null);
            }

            $this->em->flush();
            $io->writeln(sprintf(
                '  [%d] property #%d → microdistrict=%s, complex=%s',
                $processed,
                $propertyIdValue,
                $micro?->getName() ?? '—',
                $complex?->getName() ?? '—',
            ));
        }

        $io->success(sprintf(
            'Processed: %d, microdistrict linked: %d, complex linked: %d.',
            $processed,
            $linkedMicro,
            $linkedComplex,
        ));

        return Command::SUCCESS;
    }
}
