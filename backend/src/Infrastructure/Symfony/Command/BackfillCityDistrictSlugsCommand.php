<?php

declare(strict_types=1);

namespace App\Infrastructure\Symfony\Command;

use App\Domain\Property\Repository\CityDistrictRepositoryInterface;
use App\Infrastructure\Service\CityDistrictSlugGenerator;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

#[AsCommand(
    name: 'app:backfill-city-district-slugs',
    description: 'Generate URL slugs for existing city_districts rows',
)]
final class BackfillCityDistrictSlugsCommand extends Command
{
    public function __construct(
        private readonly CityDistrictRepositoryInterface $cityDistrictRepository,
        private readonly CityDistrictSlugGenerator $cityDistrictSlugGenerator,
    ) {
        parent::__construct();
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);

        $districts = $this->cityDistrictRepository->findAllWithoutSlug();
        if ($districts === []) {
            $io->success('Все районы уже имеют slug.');

            return Command::SUCCESS;
        }

        $updated = 0;

        foreach ($districts as $district) {
            $slug = $this->cityDistrictSlugGenerator->generateSlug(
                $district->getCityId(),
                $district->getName(),
            );
            $district->setSlug($slug);
            $this->cityDistrictRepository->save($district);
            ++$updated;

            $io->writeln(sprintf(
                '  #%d %s → %s',
                $district->getId(),
                $district->getName(),
                $slug,
            ));
        }

        $io->success(sprintf('Slug сгенерирован для %d район(ов).', $updated));

        return Command::SUCCESS;
    }
}
