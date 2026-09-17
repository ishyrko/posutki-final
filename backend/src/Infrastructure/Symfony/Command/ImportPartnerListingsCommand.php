<?php

declare(strict_types=1);

namespace App\Infrastructure\Symfony\Command;

use App\Domain\Property\Entity\City;
use App\Domain\Property\Entity\Property;
use App\Domain\Property\Entity\Street;
use App\Domain\Property\Enum\DealType;
use App\Domain\Property\Enum\PropertyType;
use App\Domain\Property\Enum\SellerType;
use App\Domain\Property\Repository\CityRepositoryInterface;
use App\Domain\Property\Repository\MetroStationRepositoryInterface;
use App\Domain\Property\Repository\PropertyMetroStationRepositoryInterface;
use App\Domain\Property\Repository\PropertyRepositoryInterface;
use App\Domain\Property\Repository\StreetRepositoryInterface;
use App\Domain\Property\Service\CityDistrictResolverInterface;
use App\Domain\Property\Service\CityMicrodistrictResolverInterface;
use App\Domain\Property\Service\ResidentialComplexResolverInterface;
use App\Domain\Property\Validation\PropertyDailyPriceValidator;
use App\Domain\Property\Validation\PropertyImageLimitsValidator;
use App\Domain\Property\ValueObject\Address;
use App\Domain\Property\ValueObject\Coordinates;
use App\Domain\Property\ValueObject\Price;
use App\Domain\Shared\ValueObject\Id;
use App\Domain\User\Repository\UserRepositoryInterface;
use App\Infrastructure\Import\PartnerAmenityMapper;
use App\Infrastructure\Import\PartnerCityMatcher;
use App\Infrastructure\Import\PartnerJunkImageDetector;
use App\Infrastructure\Import\PartnerListingDescriptionBuilder;
use App\Infrastructure\Import\PartnerListingTitleBuilder;
use App\Infrastructure\Service\ExchangeRateService;
use App\Infrastructure\Service\FileUploader;
use App\Infrastructure\Service\LandmarkProximityCalculator;
use App\Infrastructure\Service\MetroProximityCalculator;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;
use Symfony\Component\HttpFoundation\File\UploadedFile;

#[AsCommand(
    name: 'app:import-partner-listings',
    description: 'Import partner listings from a scraped listings.json (idempotent by external_source+external_id)',
)]
final class ImportPartnerListingsCommand extends Command
{
    public function __construct(
        private readonly UserRepositoryInterface $userRepository,
        private readonly PropertyRepositoryInterface $propertyRepository,
        private readonly CityRepositoryInterface $cityRepository,
        private readonly StreetRepositoryInterface $streetRepository,
        private readonly FileUploader $fileUploader,
        private readonly ExchangeRateService $exchangeRateService,
        private readonly MetroProximityCalculator $metroProximityCalculator,
        private readonly LandmarkProximityCalculator $landmarkProximityCalculator,
        private readonly CityDistrictResolverInterface $cityDistrictResolver,
        private readonly CityMicrodistrictResolverInterface $cityMicrodistrictResolver,
        private readonly ResidentialComplexResolverInterface $residentialComplexResolver,
        private readonly PartnerAmenityMapper $amenityMapper,
        private readonly PartnerJunkImageDetector $junkImageDetector,
        private readonly PartnerListingTitleBuilder $titleBuilder,
        private readonly PartnerListingDescriptionBuilder $descriptionBuilder,
        private readonly PropertyMetroStationRepositoryInterface $propertyMetroStationRepository,
        private readonly MetroStationRepositoryInterface $metroStationRepository,
    ) {
        parent::__construct();
    }

    protected function configure(): void
    {
        $this
            ->addOption('owner', null, InputOption::VALUE_REQUIRED, 'User id of the partner account')
            ->addOption('source', null, InputOption::VALUE_REQUIRED, 'Path to listings.json')
            ->addOption('dry-run', null, InputOption::VALUE_NONE, 'Validate and report without writing')
            ->addOption('limit', null, InputOption::VALUE_REQUIRED, 'Max listings to process', '0')
            ->addOption('force-images', null, InputOption::VALUE_NONE, 'Re-upload photos on update');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);
        $ownerIdRaw = (string) $input->getOption('owner');
        $sourcePath = (string) $input->getOption('source');
        $dryRun = (bool) $input->getOption('dry-run');
        $forceImages = (bool) $input->getOption('force-images');
        $limit = max(0, (int) $input->getOption('limit'));

        if ($ownerIdRaw === '' || $sourcePath === '') {
            $io->error('Нужны --owner и --source');

            return Command::INVALID;
        }

        $owner = $this->userRepository->findById(Id::fromString($ownerIdRaw));
        if ($owner === null) {
            $io->error('Пользователь не найден: ' . $ownerIdRaw);

            return Command::FAILURE;
        }

        if (!is_file($sourcePath)) {
            $io->error('Файл не найден: ' . $sourcePath);

            return Command::FAILURE;
        }

        $decoded = json_decode((string) file_get_contents($sourcePath), true);
        if (!is_array($decoded)) {
            $io->error('Некорректный JSON');

            return Command::FAILURE;
        }

        $sourceName = is_string($decoded['source'] ?? null) && $decoded['source'] !== ''
            ? (string) $decoded['source']
            : 'arendom';
        /** @var list<mixed> $rows */
        $rows = is_array($decoded['listings'] ?? null) ? $decoded['listings'] : [];
        $baseDir = dirname($sourcePath);
        $fingerprintCounts = PartnerListingDescriptionBuilder::fingerprintCountsFromRows($rows);

        $created = 0;
        $updated = 0;
        $skipped = 0;
        $processed = 0;
        /** @var array<int, array<string, true>> $usedTitles */
        $usedTitles = [];

        foreach ($rows as $index => $row) {
            if ($limit > 0 && $processed >= $limit) {
                break;
            }
            if (!is_array($row)) {
                ++$skipped;
                $io->warning(sprintf('#%d: пропуск — запись не объект', $index));
                continue;
            }

            ++$processed;
            $externalId = trim((string) ($row['externalId'] ?? ''));
            if ($externalId === '') {
                ++$skipped;
                $io->warning(sprintf('#%d: пропуск — нет externalId', $index));
                continue;
            }

            $resolved = $this->resolveRow($row, $sourceName, $externalId, $fingerprintCounts);
            if ($resolved['skip'] !== null) {
                ++$skipped;
                $io->warning(sprintf('%s/%s: %s', $sourceName, $externalId, $resolved['skip']));
                continue;
            }

            $existing = $this->propertyRepository->findByExternalSourceAndId($sourceName, $externalId);
            $uploadableRefs = $this->collectUploadableImageRefs($resolved['imageRefs'], $baseDir);

            if ($existing === null) {
                $resolved['title'] = $this->uniquifyTitle($resolved, $usedTitles, null);
                $imageCount = $dryRun ? count($uploadableRefs) : 0;
                if (!$dryRun) {
                    $resolved['images'] = $this->uploadImages($uploadableRefs, $baseDir);
                    $imageCount = count($resolved['images']);
                }
                $resolved['asDraft'] = $this->shouldStayDraft($resolved, $imageCount);

                if ($dryRun) {
                    $io->writeln(sprintf(
                        '[dry-run] создать %s/%s — %s (%s)',
                        $sourceName,
                        $externalId,
                        $resolved['title'],
                        $resolved['asDraft'] ? 'draft' : 'publish',
                    ));
                    $io->writeln('  ' . $resolved['description']);
                    ++$created;
                    continue;
                }

                $this->createListing($owner->getId(), $resolved, $owner->isTrustedPublisher());
                ++$created;
                $io->writeln(sprintf('создано %s/%s — %s', $sourceName, $externalId, $resolved['title']));
                continue;
            }

            $reloadImages = $this->shouldReloadImages($existing, $uploadableRefs, $forceImages);
            if ($dryRun) {
                $io->writeln(sprintf(
                    '[dry-run] обновить %s/%s — тексты сохранены без изменений, фото %s',
                    $sourceName,
                    $externalId,
                    $reloadImages ? 'будут загружены заново' : 'без изменений',
                ));
                $io->writeln(sprintf('  сгенерировано (не применяется): %s', $resolved['title']));
                $io->writeln('  ' . $resolved['description']);
                ++$updated;
                continue;
            }

            $images = null;
            if ($reloadImages) {
                $images = $this->uploadImages($uploadableRefs, $baseDir);
            }
            $this->updateListing($existing, $resolved, $images);
            ++$updated;
            $io->writeln(sprintf(
                'обновлено %s/%s — тексты сохранены без изменений%s',
                $sourceName,
                $externalId,
                $reloadImages ? ', фото загружены заново' : '',
            ));
        }

        $io->success(sprintf(
            'Готово. создано: %d, обновлено: %d, пропущено: %d, обработано: %d%s',
            $created,
            $updated,
            $skipped,
            $processed,
            $dryRun ? ' (dry-run)' : '',
        ));

        return Command::SUCCESS;
    }

    /**
     * @param array<string, mixed> $row
     * @param array<string, int> $fingerprintCounts
     *
     * @return array{
     *     skip: ?string,
     *     title: string,
     *     description: string,
     *     useGeneratedDescription: bool,
     *     city: ?City,
     *     street: ?Street,
     *     streetName: ?string,
     *     streetLabel: ?string,
     *     building: string,
     *     coordinates: ?Coordinates,
     *     imageRefs: list<string>,
     *     images: list<string>,
     *     amenities: list<string>,
     *     price: Price,
     *     priceByn: int,
     *     priceAmount: int,
     *     area: float,
     *     rawArea: float,
     *     rooms: ?int,
     *     floor: ?int,
     *     totalFloors: ?int,
     *     bathrooms: ?int,
     *     maxDailyGuests: int,
     *     guestsForCopy: ?int,
     *     dailySingleBeds: int,
     *     dailyDoubleBeds: int,
     *     checkInTime: string,
     *     checkOutTime: string,
     *     minStayDays: int,
     *     asDraft: bool,
     *     externalSource: string,
     *     externalId: string,
     * }
     */
    private function resolveRow(
        array $row,
        string $sourceName,
        string $externalId,
        array $fingerprintCounts,
    ): array {
        $partnerDescription = trim((string) ($row['description'] ?? ''));
        $cityName = trim((string) ($row['cityName'] ?? ''));
        $streetName = trim((string) ($row['streetName'] ?? ''));
        $building = trim((string) ($row['building'] ?? ''));
        $priceAmount = (int) ($row['priceByn'] ?? 0);
        $area = (float) ($row['area'] ?? 0);
        $imageRefs = [];
        foreach (is_array($row['images'] ?? null) ? $row['images'] : [] as $ref) {
            if (is_string($ref) && $ref !== '') {
                $imageRefs[] = $ref;
            }
        }
        $amenityLabels = is_array($row['amenities'] ?? null) ? $row['amenities'] : [];

        $city = $this->resolveCity($cityName);
        if ($city === null) {
            return $this->skippedResult('город не найден: ' . $cityName);
        }

        $street = $streetName !== '' ? $this->resolveStreet($city->getId(), $streetName) : null;
        $coordinates = $this->resolveCoordinates($row);
        if ($coordinates === null) {
            return $this->skippedResult('нет координат');
        }

        if ($building === '') {
            $building = '1';
        }

        $rooms = isset($row['rooms']) ? (int) $row['rooms'] : null;
        $maxDailyGuests = max(1, (int) ($row['maxDailyGuests'] ?? 2));
        $guestsForCopy = $this->resolveGuestsForCopy($row, $rooms, $maxDailyGuests);
        $streetLabel = $street?->getName() ?? ($streetName !== '' ? $streetName : null);
        $checkInTime = $this->normalizeTime((string) ($row['checkInTime'] ?? '14:00'));
        $checkOutTime = $this->normalizeTime((string) ($row['checkOutTime'] ?? '12:00'));
        $minStayDays = max(1, (int) ($row['minStayDays'] ?? 1));

        $useGeneratedDescription = PartnerListingDescriptionBuilder::isTemplate(
            $partnerDescription,
            $fingerprintCounts,
        );
        if ($useGeneratedDescription) {
            $description = $this->descriptionBuilder->build(
                rooms: $rooms,
                guests: $guestsForCopy,
                city: $city,
                streetLabel: $streetLabel,
                building: $building,
                externalId: $externalId,
                hasBusinessDocs: PartnerListingDescriptionBuilder::mentionsBusinessDocs($partnerDescription),
                minStayDays: $minStayDays,
                checkInTime: $checkInTime,
                checkOutTime: $checkOutTime,
            );
        } else {
            $description = $partnerDescription;
            if ($description === '') {
                $description = sprintf('Посуточная аренда квартиры в городе %s.', $city->getName());
            }
            if (mb_strlen($description) < 50) {
                $description .= ' Подробности уточняйте у владельца при бронировании.';
            }
        }

        $title = $this->titleBuilder->build($rooms, $guestsForCopy, $city, $streetLabel);

        $price = Price::fromAmount($priceAmount, 'BYN');
        $priceByn = $this->exchangeRateService->calculatePriceByn($priceAmount, 'BYN');

        return [
            'skip' => null,
            'title' => $title,
            'description' => $description,
            'useGeneratedDescription' => $useGeneratedDescription,
            'city' => $city,
            'street' => $street,
            'streetName' => $street?->getName() ?? ($streetName !== '' ? $streetName : null),
            'streetLabel' => $streetLabel,
            'building' => $building,
            'coordinates' => $coordinates,
            'imageRefs' => $imageRefs,
            'images' => [],
            'amenities' => $this->amenityMapper->map(array_map(
                static fn(mixed $label): string => is_string($label) ? $label : '',
                $amenityLabels,
            )),
            'price' => $price,
            'priceByn' => $priceByn,
            'priceAmount' => $priceAmount,
            'area' => $area > 0 ? $area : 30.0,
            'rawArea' => $area,
            'rooms' => $rooms,
            'floor' => isset($row['floor']) ? (int) $row['floor'] : null,
            'totalFloors' => isset($row['totalFloors']) ? (int) $row['totalFloors'] : null,
            'bathrooms' => isset($row['bathrooms']) ? (int) $row['bathrooms'] : 1,
            'maxDailyGuests' => $maxDailyGuests,
            'guestsForCopy' => $guestsForCopy,
            'dailySingleBeds' => max(0, (int) ($row['dailySingleBeds'] ?? 2)),
            'dailyDoubleBeds' => max(0, (int) ($row['dailyDoubleBeds'] ?? 0)),
            'checkInTime' => $checkInTime,
            'checkOutTime' => $checkOutTime,
            'minStayDays' => $minStayDays,
            'asDraft' => false,
            'externalSource' => $sourceName,
            'externalId' => $externalId,
        ];
    }

    /**
     * @param array<string, mixed> $resolved
     * @param array<int, array<string, true>> $usedTitles
     */
    private function uniquifyTitle(array $resolved, array &$usedTitles, ?int $excludePropertyId): string
    {
        /** @var City $city */
        $city = $resolved['city'];
        $title = $this->titleBuilder->build(
            $resolved['rooms'],
            $resolved['guestsForCopy'],
            $city,
            $resolved['streetLabel'],
        );
        if ($this->titleIsTaken($city->getId(), $title, $usedTitles, $excludePropertyId)) {
            $title = $this->titleBuilder->build(
                $resolved['rooms'],
                $resolved['guestsForCopy'],
                $city,
                $resolved['streetLabel'],
                true,
            );
        }
        $usedTitles[$city->getId()][$title] = true;

        return $title;
    }

    /**
     * @param array<int, array<string, true>> $usedTitles
     */
    private function titleIsTaken(int $cityId, string $title, array $usedTitles, ?int $excludePropertyId): bool
    {
        if (isset($usedTitles[$cityId][$title])) {
            return true;
        }

        return $this->propertyRepository->existsByCityIdAndTitle($cityId, $title, $excludePropertyId);
    }

    /**
     * @param array<string, mixed> $row
     */
    private function resolveGuestsForCopy(array $row, ?int $rooms, int $maxDailyGuests): ?int
    {
        if (array_key_exists('guestsParsed', $row) && $row['guestsParsed'] !== null && $row['guestsParsed'] !== '') {
            $parsed = (int) $row['guestsParsed'];

            return $parsed > 0 ? $parsed : null;
        }

        if ($rooms !== null && $maxDailyGuests > $rooms) {
            return $maxDailyGuests;
        }

        return null;
    }

    /**
     * @param array<string, mixed> $resolved
     */
    private function shouldStayDraft(array $resolved, int $imageCount): bool
    {
        $description = (string) $resolved['description'];

        return $imageCount < PropertyImageLimitsValidator::MIN
            || mb_strlen($description) < 50
            || (float) $resolved['rawArea'] <= 0
            || (int) $resolved['priceAmount'] < PropertyDailyPriceValidator::MIN_DAILY_PRICE_BYN;
    }

    /**
     * @param list<string> $uploadableRefs
     */
    private function shouldReloadImages(Property $property, array $uploadableRefs, bool $forceImages): bool
    {
        if ($forceImages) {
            return true;
        }

        $existing = $property->getImages();
        if ($existing === []) {
            return true;
        }

        return count($existing) !== count($uploadableRefs);
    }

    /**
     * @param list<string> $imageRefs
     *
     * @return list<string>
     */
    private function collectUploadableImageRefs(array $imageRefs, string $baseDir): array
    {
        $uploadable = [];
        foreach ($imageRefs as $ref) {
            $path = $this->resolveImagePath($ref, $baseDir);
            if ($path === null || $this->junkImageDetector->isJunk($path)) {
                continue;
            }
            $uploadable[] = $ref;
            if (count($uploadable) >= PropertyImageLimitsValidator::MAX_APARTMENT) {
                break;
            }
        }

        return $uploadable;
    }

    /**
     * @param list<string> $imageRefs
     *
     * @return list<string>
     */
    private function uploadImages(array $imageRefs, string $baseDir): array
    {
        $imageUrls = [];
        foreach ($imageRefs as $ref) {
            $uploaded = $this->uploadImage($ref, $baseDir);
            if ($uploaded !== null) {
                $imageUrls[] = $uploaded;
            }
            if (count($imageUrls) >= PropertyImageLimitsValidator::MAX_APARTMENT) {
                break;
            }
        }

        return $imageUrls;
    }

    /**
     * @param array<string, mixed> $resolved
     */
    private function createListing(Id $ownerId, array $resolved, bool $trusted): void
    {
        /** @var City $city */
        $city = $resolved['city'];
        /** @var Coordinates $coordinates */
        $coordinates = $resolved['coordinates'];
        /** @var Price $price */
        $price = $resolved['price'];

        $property = new Property(
            ownerId: $ownerId,
            type: PropertyType::Apartment->value,
            dealType: DealType::Daily->value,
            title: $resolved['title'],
            description: $resolved['description'],
            price: $price,
            area: $resolved['area'],
            rooms: $resolved['rooms'],
            floor: $resolved['floor'],
            totalFloors: $resolved['totalFloors'],
            bathrooms: $resolved['bathrooms'],
            yearBuilt: null,
            renovation: null,
            balcony: null,
            livingArea: null,
            kitchenArea: null,
            dealConditions: null,
            paymentMethods: null,
            maxDailyGuests: $resolved['maxDailyGuests'],
            dailySingleBeds: $resolved['dailySingleBeds'],
            dailyDoubleBeds: $resolved['dailyDoubleBeds'],
            checkInTime: $resolved['checkInTime'],
            checkOutTime: $resolved['checkOutTime'],
            address: Address::create($resolved['building'], null),
            cityId: $city->getId(),
            coordinates: $coordinates,
            streetId: $resolved['street']?->getId(),
            streetName: $resolved['streetName'],
            images: $resolved['images'],
            amenities: $resolved['amenities'],
            sellerType: SellerType::Business->value,
            minStayDays: $resolved['minStayDays'],
        );
        $property->setExternalIdentity($resolved['externalSource'], $resolved['externalId']);
        $property->setPriceByn($resolved['priceByn']);

        if (!$resolved['asDraft']) {
            $property->publish();
        }

        $this->propertyRepository->save($property);
        $this->syncPlaces($property, $coordinates, $city->getId());

        if ($resolved['useGeneratedDescription']) {
            $stationName = $this->nearestMetroStationName($property);
            if ($stationName !== null) {
                $property->update(description: $this->descriptionBuilder->appendMetro(
                    $property->getDescription(),
                    $stationName,
                ));
            }
        }

        if (!$resolved['asDraft'] && $trusted && $property->getStatus() === 'moderation') {
            $property->approve(grantFreeTrial: false, withinFreeLimit: true);
        }

        $this->propertyRepository->save($property);
    }

    /**
     * @param array<string, mixed> $resolved
     * @param list<string>|null $images
     */
    private function updateListing(Property $property, array $resolved, ?array $images): void
    {
        /** @var City $city */
        $city = $resolved['city'];
        /** @var Coordinates $coordinates */
        $coordinates = $resolved['coordinates'];
        /** @var Price $price */
        $price = $resolved['price'];

        $property->update(
            price: $price,
            area: $resolved['area'],
            rooms: $resolved['rooms'],
            floor: $resolved['floor'],
            totalFloors: $resolved['totalFloors'],
            bathrooms: $resolved['bathrooms'],
            maxDailyGuests: $resolved['maxDailyGuests'],
            dailySingleBeds: $resolved['dailySingleBeds'],
            dailyDoubleBeds: $resolved['dailyDoubleBeds'],
            checkInTime: $resolved['checkInTime'],
            checkOutTime: $resolved['checkOutTime'],
            minStayDays: $resolved['minStayDays'],
            address: Address::create($resolved['building'], null),
            cityId: $city->getId(),
            streetId: $resolved['street']?->getId(),
            streetName: $resolved['streetName'],
            coordinates: $coordinates,
            images: $images,
            amenities: $resolved['amenities'],
            sellerType: SellerType::Business->value,
        );
        $property->setPriceByn($resolved['priceByn']);
        $property->setExternalIdentity($resolved['externalSource'], $resolved['externalId']);
        $this->syncPlaces($property, $coordinates, $city->getId());
        $this->propertyRepository->save($property);
    }

    private function nearestMetroStationName(Property $property): ?string
    {
        $links = $this->propertyMetroStationRepository->findByPropertyId($property->getId()->getValue());
        $nearest = $links[0] ?? null;
        if ($nearest === null) {
            return null;
        }

        $station = $this->metroStationRepository->findById($nearest->getMetroStationId());

        return $station?->getName();
    }

    private function syncPlaces(Property $property, Coordinates $coordinates, int $cityId): void
    {
        $cityDistrict = $this->cityDistrictResolver->resolve(
            $coordinates->getLatitude(),
            $coordinates->getLongitude(),
            $cityId,
            $property->getId()->getValue(),
        );
        $property->setCityDistrictId($cityDistrict?->getId());

        $microdistrict = $this->cityMicrodistrictResolver->resolve(
            $coordinates->getLatitude(),
            $coordinates->getLongitude(),
            $cityId,
            $property->getId()->getValue(),
        );
        $property->setCityMicrodistrictId($microdistrict?->getId());

        $complex = $this->residentialComplexResolver->resolve(
            $coordinates->getLatitude(),
            $coordinates->getLongitude(),
            $cityId,
            $property->getId()->getValue(),
        );
        $property->setResidentialComplexId($complex?->getId());

        $this->metroProximityCalculator->syncForProperty($property);
        $this->landmarkProximityCalculator->syncForProperty($property);
    }

    private function resolveCity(string $name): ?City
    {
        $name = trim($name);
        if ($name === '') {
            return null;
        }

        $aliases = [
            'могилев' => 'Могилёв',
            'могилёв' => 'Могилёв',
        ];
        $lookup = $aliases[mb_strtolower($name)] ?? $name;

        return PartnerCityMatcher::pick(
            $lookup,
            $this->cityRepository->searchByName($lookup, null, 50),
        );
    }

    private function resolveStreet(int $cityId, string $name): ?Street
    {
        $candidates = $this->streetRepository->searchByCityId($cityId, $name);
        foreach ($candidates as $candidate) {
            if (mb_strtolower($candidate->getName()) === mb_strtolower($name)) {
                return $candidate;
            }
        }

        return $candidates[0] ?? null;
    }

    /**
     * @param array<string, mixed> $row
     */
    private function resolveCoordinates(array $row): ?Coordinates
    {
        $lat = isset($row['latitude']) ? (float) $row['latitude'] : 0.0;
        $lon = isset($row['longitude']) ? (float) $row['longitude'] : 0.0;
        if ($lat === 0.0 || $lon === 0.0) {
            return null;
        }

        return Coordinates::create($lat, $lon);
    }

    private function resolveImagePath(string $ref, string $baseDir): ?string
    {
        $path = $ref;
        if (!str_starts_with($ref, '/') && !preg_match('#^[a-z][a-z0-9+.-]*://#i', $ref)) {
            $path = $baseDir . '/' . ltrim($ref, '/');
        }

        return is_file($path) ? $path : null;
    }

    private function uploadImage(string $ref, string $baseDir): ?string
    {
        $path = $this->resolveImagePath($ref, $baseDir);
        if ($path === null) {
            return null;
        }
        if ($this->junkImageDetector->isJunk($path)) {
            return null;
        }

        $mime = mime_content_type($path) ?: 'image/jpeg';
        $tmp = tempnam(sys_get_temp_dir(), 'impimg');
        if ($tmp === false) {
            return null;
        }
        copy($path, $tmp);

        $uploaded = new UploadedFile($tmp, basename($path), $mime, null, true);
        $relative = $this->fileUploader->upload($uploaded, FileUploader::SCOPE_PROPERTIES);

        return '/uploads/' . ltrim($relative, '/');
    }

    private function normalizeTime(string $value): string
    {
        if (preg_match('/^(\d{1,2}):(\d{2})/', trim($value), $matches) === 1) {
            return sprintf('%02d:%02d', (int) $matches[1], (int) $matches[2]);
        }

        return '14:00';
    }

    /**
     * @return array<string, mixed>
     */
    private function skippedResult(string $reason): array
    {
        return [
            'skip' => $reason,
            'title' => '',
            'description' => '',
            'useGeneratedDescription' => false,
            'city' => null,
            'street' => null,
            'streetName' => null,
            'streetLabel' => null,
            'building' => '',
            'coordinates' => null,
            'imageRefs' => [],
            'images' => [],
            'amenities' => [],
            'price' => Price::fromAmount(0, 'BYN'),
            'priceByn' => 0,
            'priceAmount' => 0,
            'area' => 0.0,
            'rawArea' => 0.0,
            'rooms' => null,
            'floor' => null,
            'totalFloors' => null,
            'bathrooms' => null,
            'maxDailyGuests' => 1,
            'guestsForCopy' => null,
            'dailySingleBeds' => 0,
            'dailyDoubleBeds' => 0,
            'checkInTime' => '14:00',
            'checkOutTime' => '12:00',
            'minStayDays' => 1,
            'asDraft' => true,
            'externalSource' => '',
            'externalId' => '',
        ];
    }
}
