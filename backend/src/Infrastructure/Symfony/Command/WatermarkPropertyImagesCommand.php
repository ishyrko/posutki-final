<?php

declare(strict_types=1);

namespace App\Infrastructure\Symfony\Command;

use App\Domain\Property\Entity\Property;
use App\Domain\Property\Entity\PropertyRevision;
use App\Domain\Property\Repository\PropertyRevisionRepositoryInterface;
use App\Infrastructure\Service\FileUploader;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

#[AsCommand(
    name: 'app:watermark-property-images',
    description: 'Проставить водяной знак на локальные фото объявлений, при необходимости конвертировать в WebP',
)]
final class WatermarkPropertyImagesCommand extends Command
{
    public function __construct(
        private readonly EntityManagerInterface $em,
        private readonly FileUploader $fileUploader,
        private readonly PropertyRevisionRepositoryInterface $revisionRepository,
    ) {
        parent::__construct();
    }

    protected function configure(): void
    {
        $this->addOption('dry-run', null, InputOption::VALUE_NONE, 'Показать изменения без записи в БД и файлов');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);
        $dryRun = (bool) $input->getOption('dry-run');

        if ($dryRun) {
            $io->note('Режим dry-run: файлы и БД не будут изменены.');
        }

        /** @var Property[] $properties */
        $properties = $this->em->getRepository(Property::class)->findAll();
        $processedImages = 0;
        $updatedProperties = 0;
        $updatedRevisions = 0;
        $failedImages = 0;
        $rewatermarkedSameUrl = 0;

        foreach ($properties as $property) {
            $images = $property->getImages();
            $propertyChanged = false;
            $propertyReplacements = [];

            foreach ($images as $index => $imageUrl) {
                if (!is_string($imageUrl) || !$this->fileUploader->isLocalPropertyImageUrl($imageUrl)) {
                    continue;
                }

                if ($dryRun) {
                    $io->writeln(sprintf(
                        'Property #%d: обработать %s',
                        $property->getId()->getValue(),
                        $imageUrl,
                    ));
                    ++$processedImages;
                    continue;
                }

                try {
                    $result = $this->fileUploader->processExistingPropertyImage($imageUrl);
                    ++$processedImages;

                    if ($result === null) {
                        ++$rewatermarkedSameUrl;
                        continue;
                    }

                    $images[$index] = $result['newUrl'];
                    $propertyChanged = true;
                    $propertyReplacements[$result['oldUrl']] = $result['newUrl'];
                    $io->writeln(sprintf('%s -> %s', $result['oldUrl'], $result['newUrl']));
                } catch (\Throwable $e) {
                    ++$failedImages;
                    $io->warning(sprintf(
                        'Property #%d: пропуск %s — %s',
                        $property->getId()->getValue(),
                        $imageUrl,
                        $e->getMessage(),
                    ));
                }
            }

            if ($propertyChanged) {
                $property->setImages(array_values($images));
                ++$updatedProperties;
            }

            $revision = $this->revisionRepository->findLatestByPropertyAndStatus(
                $property->getId()->getValue(),
                PropertyRevision::STATUS_PENDING,
            );

            if ($revision !== null && $propertyReplacements !== []) {
                $revision->replaceImageUrls($propertyReplacements);
                ++$updatedRevisions;
            }

            // Flush после каждого объявления: при остановке уже обработанные URL сохранятся в БД.
            if (!$dryRun && ($propertyChanged || $propertyReplacements !== [])) {
                $this->em->flush();
            }
        }

        $io->success(sprintf(
            'Обработано изображений: %d. Обновлено объявлений: %d. Обновлено ревизий: %d. Без смены URL: %d. Ошибок: %d.',
            $processedImages,
            $updatedProperties,
            $updatedRevisions,
            $rewatermarkedSameUrl,
            $failedImages,
        ));

        return $failedImages > 0 ? Command::FAILURE : Command::SUCCESS;
    }
}
