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
use App\Infrastructure\Service\ExchangeRateService;
use App\Infrastructure\Service\FileUploader;
use App\Infrastructure\Service\LandmarkProximityCalculator;
use App\Infrastructure\Service\MetroProximityCalculator;
use App\Infrastructure\Service\YandexForwardGeocoder;
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
        private readonly YandexForwardGeocoder $forwardGeocoder,
        private readonly ExchangeRateService $exchangeRateService,
        private readonly MetroProximityCalculator $metroProximityCalculator,
        private readonly LandmarkProximityCalculator $landmarkProximityCalculator,
        private readonly CityDistrictResolverInterface $cityDistrictResolver,
        private readonly CityMicrodistrictResolverInterface $cityMicrodistrictResolver,
        private readonly ResidentialComplexResolverInterface $residentialComplexResolver,
        private readonly PartnerAmenityMapper $amenityMapper,
    ) {
        parent::__construct();
    }

    protected function configure(): void
    {
        $this
            ->addOption('owner', null, InputOption::VALUE_REQUIRED, 'User id of the partner account')
            ->addOption('source', null, InputOption::VALUE_REQUIRED, 'Path to listings.json')
            ->addOption('dry-run', null, InputOption::VALUE_NONE, 'Validate and report without writing')
            ->addOption('limit', null, InputOption::VALUE_REQUIRED, 'Max listings to process', '0');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);
        $ownerIdRaw = (string) $input->getOption('owner');
        $sourcePath = (string) $input->getOption('source');
        $dryRun = (bool) $input->getOption('dry-run');
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

        $created = 0;
        $updated = 0;
        $skipped = 0;
        $processed = 0;

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

            $resolved = $this->resolveRow($row, $baseDir, $sourceName, $externalId, $dryRun);
            if ($resolved['skip'] !== null) {
                ++$skipped;
                $io->warning(sprintf('%s/%s: %s', $sourceName, $externalId, $resolved['skip']));
                continue;
            }

            if ($dryRun) {
                $io->writeln(sprintf(
                    '[dry-run] %s/%s — %s (%s)',
                    $sourceName,
                    $externalId,
                    $resolved['title'],
                    $resolved['asDraft'] ? 'draft' : 'publish',
                ));
                continue;
            }

            $existing = $this->propertyRepository->findByExternalSourceAndId($sourceName, $externalId);
            if ($existing === null) {
                $this->createListing($owner->getId(), $resolved, $owner->isTrustedPublisher());
                ++$created;
                $io->writeln(sprintf('создано %s/%s', $sourceName, $externalId));
            } else {
                $this->updateListing($existing, $resolved);
                ++$updated;
                $io->writeln(sprintf('обновлено %s/%s', $sourceName, $externalId));
            }
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
     *
     * @return array{
     *     skip: ?string,
     *     title: string,
     *     description: string,
     *     city: ?City,
     *     street: ?Street,
     *     streetName: ?string,
     *     building: string,
     *     coordinates: ?Coordinates,
     *     images: list<string>,
     *     amenities: list<string>,
     *     price: Price,
     *     priceByn: int,
     *     area: float,
     *     rooms: ?int,
     *     floor: ?int,
     *     totalFloors: ?int,
     *     bathrooms: ?int,
     *     maxDailyGuests: int,
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
    private function resolveRow(array $row, string $baseDir, string $sourceName, string $externalId, bool $dryRun): array
    {
        $title = trim((string) ($row['title'] ?? ''));
        $description = trim((string) ($row['description'] ?? ''));
        $cityName = trim((string) ($row['cityName'] ?? ''));
        $streetName = trim((string) ($row['streetName'] ?? ''));
        $building = trim((string) ($row['building'] ?? ''));
        $priceAmount = (int) ($row['priceByn'] ?? 0);
        $area = (float) ($row['area'] ?? 0);
        $imageRefs = is_array($row['images'] ?? null) ? $row['images'] : [];
        $amenityLabels = is_array($row['amenities'] ?? null) ? $row['amenities'] : [];

        $city = $this->resolveCity($cityName);
        if ($city === null) {
            return $this->skippedResult('город не найден: ' . $cityName);
        }

        $street = $streetName !== '' ? $this->resolveStreet($city->getId(), $streetName) : null;
        $coordinates = $this->resolveCoordinates($row, $cityName, $streetName, $building);
        if ($coordinates === null) {
            return $this->skippedResult('нет координат и геокодер не ответил');
        }

        if ($building === '') {
            $building = '1';
        }
        if ($title === '') {
            $title = sprintf('Квартира в г. %s', $city->getName());
        }
        if (mb_strlen($title) < 10) {
            $title = $title . ', ' . $city->getName();
        }
        if (mb_strlen($title) > 200) {
            $title = mb_substr($title, 0, 200);
        }

        $imageUrls = [];
        if (!$dryRun) {
            foreach ($imageRefs as $ref) {
                if (!is_string($ref) || $ref === '') {
                    continue;
                }
                $uploaded = $this->uploadImage($ref, $baseDir);
                if ($uploaded !== null) {
                    $imageUrls[] = $uploaded;
                }
                if (count($imageUrls) >= PropertyImageLimitsValidator::MAX_APARTMENT) {
                    break;
                }
            }
        } else {
            $imageUrls = array_values(array_filter(
                array_map(static fn(mixed $ref): string => is_string($ref) ? $ref : '', $imageRefs),
            ));
        }

        $asDraft = count($imageUrls) < PropertyImageLimitsValidator::MIN
            || mb_strlen($description) < 50
            || $area <= 0
            || $priceAmount < PropertyDailyPriceValidator::MIN_DAILY_PRICE_BYN;

        if ($description === '') {
            $description = sprintf('Посуточная аренда квартиры в городе %s.', $city->getName());
        }
        if (mb_strlen($description) < 50) {
            $description .= ' Подробности уточняйте у владельца при бронировании.';
            $asDraft = true;
        }

        $price = Price::fromAmount($priceAmount, 'BYN');
        $priceByn = $this->exchangeRateService->calculatePriceByn($priceAmount, 'BYN');

        return [
            'skip' => null,
            'title' => $title,
            'description' => $description,
            'city' => $city,
            'street' => $street,
            'streetName' => $street?->getName() ?? ($streetName !== '' ? $streetName : null),
            'building' => $building,
            'coordinates' => $coordinates,
            'images' => $imageUrls,
            'amenities' => $this->amenityMapper->map(array_map(
                static fn(mixed $label): string => is_string($label) ? $label : '',
                $amenityLabels,
            )),
            'price' => $price,
            'priceByn' => $priceByn,
            'area' => $area > 0 ? $area : 30.0,
            'rooms' => isset($row['rooms']) ? (int) $row['rooms'] : null,
            'floor' => isset($row['floor']) ? (int) $row['floor'] : null,
            'totalFloors' => isset($row['totalFloors']) ? (int) $row['totalFloors'] : null,
            'bathrooms' => isset($row['bathrooms']) ? (int) $row['bathrooms'] : 1,
            'maxDailyGuests' => max(1, (int) ($row['maxDailyGuests'] ?? 2)),
            'dailySingleBeds' => max(0, (int) ($row['dailySingleBeds'] ?? 2)),
            'dailyDoubleBeds' => max(0, (int) ($row['dailyDoubleBeds'] ?? 0)),
            'checkInTime' => $this->normalizeTime((string) ($row['checkInTime'] ?? '14:00')),
            'checkOutTime' => $this->normalizeTime((string) ($row['checkOutTime'] ?? '12:00')),
            'minStayDays' => max(1, (int) ($row['minStayDays'] ?? 1)),
            'asDraft' => $asDraft,
            'externalSource' => $sourceName,
            'externalId' => $externalId,
        ];
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

        if (!$resolved['asDraft'] && $trusted && $property->getStatus() === 'moderation') {
            $property->approve(grantFreeTrial: false, withinFreeLimit: true);
        }

        $this->propertyRepository->save($property);
    }

    /**
     * @param array<string, mixed> $resolved
     */
    private function updateListing(Property $property, array $resolved): void
    {
        /** @var City $city */
        $city = $resolved['city'];
        /** @var Coordinates $coordinates */
        $coordinates = $resolved['coordinates'];
        /** @var Price $price */
        $price = $resolved['price'];

        $property->update(
            title: $resolved['title'],
            description: $resolved['description'],
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
            images: $resolved['images'] !== [] ? $resolved['images'] : null,
            amenities: $resolved['amenities'],
            sellerType: SellerType::Business->value,
        );
        $property->setPriceByn($resolved['priceByn']);
        $property->setExternalIdentity($resolved['externalSource'], $resolved['externalId']);
        $this->syncPlaces($property, $coordinates, $city->getId());
        $this->propertyRepository->save($property);
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
            $this->cityRepository->searchByName($lookup, null, 20),
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
    private function resolveCoordinates(array $row, string $cityName, string $streetName, string $building): ?Coordinates
    {
        $lat = isset($row['latitude']) ? (float) $row['latitude'] : 0.0;
        $lon = isset($row['longitude']) ? (float) $row['longitude'] : 0.0;
        if ($lat !== 0.0 && $lon !== 0.0) {
            return Coordinates::create($lat, $lon);
        }

        $parts = array_filter(['Беларусь', $cityName, $streetName, $building], static fn(string $part): bool => $part !== '');

        return $this->forwardGeocoder->geocodeAddress(implode(', ', $parts));
    }

    private function uploadImage(string $ref, string $baseDir): ?string
    {
        $path = $ref;
        if (!str_starts_with($ref, '/') && !preg_match('#^[a-z][a-z0-9+.-]*://#i', $ref)) {
            $path = $baseDir . '/' . ltrim($ref, '/');
        }
        if (!is_file($path)) {
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
            'city' => null,
            'street' => null,
            'streetName' => null,
            'building' => '',
            'coordinates' => null,
            'images' => [],
            'amenities' => [],
            'price' => Price::fromAmount(0, 'BYN'),
            'priceByn' => 0,
            'area' => 0.0,
            'rooms' => null,
            'floor' => null,
            'totalFloors' => null,
            'bathrooms' => null,
            'maxDailyGuests' => 1,
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
