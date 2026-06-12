<?php

declare(strict_types=1);

namespace App\Infrastructure\Symfony\Command;

use App\Application\Service\IcsCalendarService;
use App\Domain\Property\Entity\Property;
use App\Domain\Property\Repository\PropertyRepositoryInterface;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

#[AsCommand(
    name: 'app:sync-external-calendars',
    description: 'Синхронизировать внешние iCal-календари объявлений и сохранить снимок занятых дат',
)]
final class SyncExternalCalendarsCommand extends Command
{
    public function __construct(
        private readonly PropertyRepositoryInterface $propertyRepository,
        private readonly IcsCalendarService $icsCalendarService,
    ) {
        parent::__construct();
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);
        $properties = $this->propertyRepository->findWithExternalCalendarUrls();

        $synced = 0;
        $failed = 0;

        foreach ($properties as $property) {
            if (!$property instanceof Property) {
                continue;
            }

            $urls = $property->getExternalCalendarUrls() ?? [];
            if ($urls === []) {
                $property->clearExternalCalendarSnapshot();
                $this->propertyRepository->save($property);
                continue;
            }

            try {
                $calendarData = $this->icsCalendarService->fetchCalendarData($urls);
                $property->setExternalCalendarSnapshot($calendarData['blockedRanges']);
                $this->propertyRepository->save($property);
                ++$synced;
            } catch (\Throwable) {
                ++$failed;
            }
        }

        $io->success(sprintf(
            'Синхронизировано объявлений: %d, ошибок: %d (всего с внешними календарями: %d).',
            $synced,
            $failed,
            count($properties),
        ));

        return Command::SUCCESS;
    }
}
