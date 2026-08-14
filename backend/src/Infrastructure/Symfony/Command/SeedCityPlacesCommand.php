<?php

declare(strict_types=1);

namespace App\Infrastructure\Symfony\Command;

use App\Domain\Property\Entity\CityMicrodistrict;
use App\Domain\Property\Entity\Property;
use App\Domain\Property\Entity\ResidentialComplex;
use App\Domain\Property\Repository\CityMicrodistrictRepositoryInterface;
use App\Domain\Property\Repository\CityRepositoryInterface;
use App\Domain\Property\Repository\ResidentialComplexRepositoryInterface;
use App\Domain\Property\Service\CuratedCityPlaces;
use App\Infrastructure\Service\CityMicrodistrictSlugGenerator;
use App\Infrastructure\Service\GeocoderPlaceExtractor;
use App\Infrastructure\Service\ResidentialComplexSlugGenerator;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

#[AsCommand(
    name: 'app:seed-city-places',
    description: 'Seed curated city microdistricts and residential complexes from geocoder cache',
)]
final class SeedCityPlacesCommand extends Command
{
    public function __construct(
        private readonly EntityManagerInterface $em,
        private readonly CityRepositoryInterface $cityRepository,
        private readonly CityMicrodistrictRepositoryInterface $microdistrictRepository,
        private readonly ResidentialComplexRepositoryInterface $residentialComplexRepository,
        private readonly GeocoderPlaceExtractor $geocoderPlaceExtractor,
        private readonly CityMicrodistrictSlugGenerator $microdistrictSlugGenerator,
        private readonly ResidentialComplexSlugGenerator $residentialComplexSlugGenerator,
    ) {
        parent::__construct();
    }

    protected function configure(): void
    {
        $this
            ->addOption('apply', null, InputOption::VALUE_NONE, 'Persist curated places and link properties')
            ->addOption('min-properties', null, InputOption::VALUE_REQUIRED, 'Minimum apartments per place in dry-run report', '2');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);
        $apply = (bool) $input->getOption('apply');
        $minProperties = max(1, (int) $input->getOption('min-properties'));

        if (!$apply) {
            $this->printDryRunReport($io, $minProperties);
            $io->note('Dry-run only. Re-run with --apply to persist curated places and link properties.');

            return Command::SUCCESS;
        }

        $createdMicro = 0;
        $createdComplex = 0;
        $linkedMicro = 0;
        $linkedComplex = 0;

        foreach (CuratedCityPlaces::all() as $entry) {
            $city = $this->cityRepository->findBySlug($entry['citySlug']);
            if ($city === null) {
                $io->warning(sprintf('City «%s» not found, skipping %s', $entry['citySlug'], $entry['officialName']));
                continue;
            }

            if ($entry['type'] === CuratedCityPlaces::PLACE_TYPE_MICRODISTRICT) {
                if ($this->microdistrictRepository->findByCityIdAndOfficialName($city->getId(), $entry['officialName']) === null) {
                    $slug = $this->microdistrictSlugGenerator->generateSlug($city->getId(), $entry['name']);
                    $this->microdistrictRepository->save(new CityMicrodistrict(
                        $city->getId(),
                        $entry['officialName'],
                        $entry['name'],
                        $entry['namePrepositional'],
                        $slug,
                    ));
                    ++$createdMicro;
                }
            } else {
                if ($this->residentialComplexRepository->findByCityIdAndOfficialName($city->getId(), $entry['officialName']) === null) {
                    $slug = $this->residentialComplexSlugGenerator->generateSlug($city->getId(), $entry['name']);
                    $this->residentialComplexRepository->save(new ResidentialComplex(
                        $city->getId(),
                        $entry['officialName'],
                        $entry['name'],
                        $entry['namePrepositional'],
                        $slug,
                    ));
                    ++$createdComplex;
                }
            }
        }

        $conn = $this->em->getConnection();
        /** @var list<array{property_id: int|string, response: string}> $rows */
        $rows = $conn->fetchAllAssociative(
            'SELECT g.property_id, g.response
             FROM property_geocoder_results g
             INNER JOIN properties p ON p.id = g.property_id
             WHERE p.type = \'apartment\'',
        );

        foreach ($rows as $row) {
            /** @var Property|null $property */
            $property = $this->em->find(Property::class, (int) $row['property_id']);
            if ($property === null) {
                continue;
            }

            $response = json_decode((string) $row['response'], true);
            if (!is_array($response)) {
                continue;
            }

            $places = $this->geocoderPlaceExtractor->extract($response);
            $cityId = $property->getCityId();

            foreach ($places->microdistrictOfficialNames as $officialName) {
                $micro = $this->microdistrictRepository->findByCityIdAndOfficialName($cityId, $officialName);
                if ($micro !== null) {
                    $property->setCityMicrodistrictId($micro->getId());
                    ++$linkedMicro;
                    break;
                }
            }

            foreach ($places->residentialComplexOfficialNames as $officialName) {
                $complex = $this->residentialComplexRepository->findByCityIdAndOfficialName($cityId, $officialName);
                if ($complex !== null) {
                    $property->setResidentialComplexId($complex->getId());
                    ++$linkedComplex;
                    break;
                }
            }
        }

        $this->em->flush();

        $io->success(sprintf(
            'Created microdistricts: %d, complexes: %d. Linked properties: microdistrict=%d, complex=%d.',
            $createdMicro,
            $createdComplex,
            $linkedMicro,
            $linkedComplex,
        ));

        return Command::SUCCESS;
    }

    private function printDryRunReport(SymfonyStyle $io, int $minProperties): void
    {
        /** @var list<array{city: string, response: string}> $rows */
        $rows = $this->em->getConnection()->fetchAllAssociative(
            'SELECT c.name AS city, g.response
             FROM property_geocoder_results g
             INNER JOIN properties p ON p.id = g.property_id
             INNER JOIN cities c ON c.id = p.city_id
             WHERE p.type = \'apartment\'',
        );

        /** @var array<string, array<string, int>> $counts */
        $counts = [];

        foreach ($rows as $row) {
            $response = json_decode((string) $row['response'], true);
            if (!is_array($response)) {
                continue;
            }

            $places = $this->geocoderPlaceExtractor->extract($response);
            $city = (string) $row['city'];

            foreach ($places->microdistrictOfficialNames as $name) {
                $key = $city.'|microdistrict|'.$name;
                $counts[$key] = ($counts[$key] ?? 0) + 1;
            }
            foreach ($places->residentialComplexOfficialNames as $name) {
                $key = $city.'|residential_complex|'.$name;
                $counts[$key] = ($counts[$key] ?? 0) + 1;
            }
        }

        arsort($counts);
        $io->section(sprintf('Candidates with >= %d apartments', $minProperties));

        foreach ($counts as $key => $count) {
            if ($count < $minProperties) {
                continue;
            }
            [$city, $type, $name] = explode('|', $key, 3);
            $io->writeln(sprintf('%s | %s | %s | %d', $city, $type, $name, $count));
        }

        $io->section('Curated list to apply');
        foreach (CuratedCityPlaces::all() as $entry) {
            $io->writeln(sprintf(
                '%s | %s | %s | %s | %s',
                $entry['citySlug'],
                $entry['type'],
                $entry['officialName'],
                $entry['name'],
                $entry['namePrepositional'],
            ));
        }
    }
}
