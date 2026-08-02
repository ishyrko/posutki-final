<?php

declare(strict_types=1);

namespace App\Infrastructure\Symfony\Command;

use App\Domain\Property\Entity\Property;
use App\Domain\Property\Repository\CityRepositoryInterface;
use App\Domain\Property\Service\CitiesWithDistricts;
use App\Domain\Property\Service\CityDistrictResolverInterface;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

#[AsCommand(
    name: 'app:backfill-city-districts',
    description: 'Заполнить район города для объявлений в городах с административными районами',
)]
final class BackfillCityDistrictsCommand extends Command
{
    public function __construct(
        private readonly EntityManagerInterface $em,
        private readonly CityRepositoryInterface $cityRepository,
        private readonly CityDistrictResolverInterface $cityDistrictResolver,
    ) {
        parent::__construct();
    }

    protected function configure(): void
    {
        $this->addOption(
            'city',
            null,
            InputOption::VALUE_REQUIRED,
            'Обработать только объявления указанного города (slug, например brest)',
        );
        $this->addOption(
            'force',
            null,
            InputOption::VALUE_NONE,
            'Пересчитать район для всех объявлений (включая уже заполненные) и обновить ответ геокодера',
        );
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);

        $cityIdsWithDistricts = [];
        $citySlugFilter = $input->getOption('city');
        if (is_string($citySlugFilter) && $citySlugFilter !== '') {
            if (!in_array($citySlugFilter, CitiesWithDistricts::SLUGS, true)) {
                $io->error(sprintf(
                    'Город «%s» не поддерживает внутригородские районы. Доступные slug: %s',
                    $citySlugFilter,
                    implode(', ', CitiesWithDistricts::SLUGS),
                ));

                return Command::FAILURE;
            }

            $city = $this->cityRepository->findBySlug($citySlugFilter);
            if ($city === null) {
                $io->error(sprintf('Город «%s» не найден в справочнике.', $citySlugFilter));

                return Command::FAILURE;
            }

            $cityIdsWithDistricts[] = $city->getId();
            $io->note(sprintf('Фильтр по городу: %s', $citySlugFilter));
        } else {
            foreach (CitiesWithDistricts::SLUGS as $slug) {
                $city = $this->cityRepository->findBySlug($slug);
                if ($city !== null) {
                    $cityIdsWithDistricts[] = $city->getId();
                }
            }
        }

        if ($cityIdsWithDistricts === []) {
            $io->warning('Не найдены города с административными районами в справочнике.');

            return Command::SUCCESS;
        }

        $force = (bool) $input->getOption('force');
        if ($force) {
            $io->note('Режим force: обрабатываются все объявления, ответ геокодера запрашивается заново.');
        }

        $dql = 'SELECT p.id FROM App\Domain\Property\Entity\Property p
                 WHERE p.cityId IN (:cityIds)';
        if (!$force) {
            $dql .= ' AND p.cityDistrictId IS NULL';
        }
        $dql .= ' ORDER BY p.id ASC';

        /** @var list<int> $propertyIds */
        $propertyIds = $this->em
            ->createQuery($dql)
            ->setParameter('cityIds', $cityIdsWithDistricts)
            ->getSingleColumnResult();

        $processed = 0;
        $resolved = 0;
        $notFound = 0;

        foreach ($propertyIds as $propertyId) {
            /** @var Property|null $property */
            $property = $this->em->find(Property::class, $propertyId);
            if ($property === null) {
                continue;
            }

            if (!$force && $property->getCityDistrictId() !== null) {
                continue;
            }

            ++$processed;
            $coordinates = $property->getCoordinates();
            $cityDistrict = $this->cityDistrictResolver->resolve(
                $coordinates->getLatitude(),
                $coordinates->getLongitude(),
                $property->getCityId(),
                $property->getId()->getValue(),
                $force,
            );

            if ($cityDistrict === null) {
                ++$notFound;
                $io->writeln(sprintf(
                    '  [%d] район не определён (property #%d)',
                    $processed,
                    $property->getId()->getValue(),
                ));
                continue;
            }

            $property->setCityDistrictId($cityDistrict->getId());
            $this->em->flush();
            ++$resolved;
            $io->writeln(sprintf(
                '  [%d] property #%d → %s',
                $processed,
                $property->getId()->getValue(),
                $cityDistrict->getName(),
            ));
        }

        $io->success(sprintf(
            'Обработано: %d, район определён: %d, не определён: %d.',
            $processed,
            $resolved,
            $notFound,
        ));

        return Command::SUCCESS;
    }
}
