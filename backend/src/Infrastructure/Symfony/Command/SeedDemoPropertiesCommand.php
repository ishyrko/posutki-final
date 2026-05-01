<?php

declare(strict_types=1);

namespace App\Infrastructure\Symfony\Command;

use App\Domain\Property\Entity\Property;
use App\Domain\Property\Enum\DealType;
use App\Domain\Property\Enum\PropertyType;
use App\Domain\Property\ValueObject\Address;
use App\Domain\Property\ValueObject\Coordinates;
use App\Domain\Property\ValueObject\Price;
use App\Domain\Shared\ValueObject\Id;
use App\Infrastructure\Service\ExchangeRateService;
use App\Infrastructure\Service\MetroProximityCalculator;
use Doctrine\DBAL\Connection;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

/**
 * Deletes all listings and inserts demo data: for each region, more than 10 listings
 * per property type (category). Город только областной центр (is_main), не деревни.
 * Photos: Unsplash, несколько случайных на объявление (первая — обложка).
 */
#[AsCommand(
    name: 'app:seed-demo-properties',
    description: 'Remove all properties and seed demo listings (>10 per property type per region)',
)]
final class SeedDemoPropertiesCommand extends Command
{
    /** More than 10 listings per (region × property type) for most types */
    private const LISTINGS_PER_CATEGORY = 12;

    /** Квартиры: больше строк с deal=sale, чтобы /prodazha/kvartiry/ (регион) показывал >10 объектов */
    private const APARTMENT_LISTINGS_PER_CATEGORY = 15;
    /** @var array<int, array{0: float, 1: float}> regionId => [lat, lon] */
    private const REGION_CENTERS = [
        1 => [52.0976, 23.7341],
        2 => [55.1904, 30.2049],
        3 => [52.4412, 30.9878],
        4 => [53.6694, 23.8131],
        5 => [53.9045, 27.5615],
        7 => [53.9006, 30.3347],
    ];

    private const UNSPLASH = '?w=1200&q=80';

    /** @var list<string> */
    private const IMAGE_POOL_APARTMENT = [
        'https://images.unsplash.com/photo-1522708323590-d24dbb6b0267',
        'https://images.unsplash.com/photo-1493809842364-78817add7ffb',
        'https://images.unsplash.com/photo-1502672260266-1c1ef2d93688',
        'https://images.unsplash.com/photo-1560448204-e02f11c3d0e2',
        'https://images.unsplash.com/photo-1600607687939-ce8a6c25118c',
        'https://images.unsplash.com/photo-1600566753190-17f0baa2a6c3',
        'https://images.unsplash.com/photo-1600210492493-0946911123ea',
        'https://images.unsplash.com/photo-1600585154526-990dced4db0d',
        'https://images.unsplash.com/photo-1586023492125-27b2c045efd7',
        'https://images.unsplash.com/photo-1554995207-c18c203602cb',
        'https://images.unsplash.com/photo-1600047509807-ba8f99d2cdde',
        'https://images.unsplash.com/photo-1600573472550-8090a5e074fe',
        'https://images.unsplash.com/photo-1502005229762-cf1b2da7c5d6',
        'https://images.unsplash.com/photo-1631679706909-1844bbd07221',
        'https://images.unsplash.com/photo-1618221195710-dd6b41faaea6',
    ];

    /** @var list<string> */
    private const IMAGE_POOL_HOUSE = [
        'https://images.unsplash.com/photo-1600596542815-ffad4c1539a9',
        'https://images.unsplash.com/photo-1568605114967-8130f3a36994',
        'https://images.unsplash.com/photo-1600585154340-be6161a56a0c',
        'https://images.unsplash.com/photo-1512917774080-9991f1c4c750',
        'https://images.unsplash.com/photo-1605276374104-dee2a0ed3cd6',
        'https://images.unsplash.com/photo-1600585154363-67ebadee2f09',
        'https://images.unsplash.com/photo-1600607687644-c7171b42498f',
        'https://images.unsplash.com/photo-1600566752355-35792bedcfea',
        'https://images.unsplash.com/photo-1605276374104-dee2a0ed3cd6',
        'https://images.unsplash.com/photo-1600607688969-a5bfcd646154',
        'https://images.unsplash.com/photo-1600585154526-990dced4db0d',
        'https://images.unsplash.com/photo-1600566753190-17f0baa2a6c3',
        'https://images.unsplash.com/photo-1600210492493-0946911123ea',
    ];

    /** @var list<string> */
    private const IMAGE_POOL_ROOM = [
        'https://images.unsplash.com/photo-1630699035186-98a5ec8d00f0',
        'https://images.unsplash.com/photo-1616594039964-ae9021a400a0',
        'https://images.unsplash.com/photo-1618216629414-0b8612ce7a96',
        'https://images.unsplash.com/photo-1616486338812-3dadae4b4f9d',
        'https://images.unsplash.com/photo-1631679706909-1844bbd07221',
        'https://images.unsplash.com/photo-1615529328331-f8917597711f',
        'https://images.unsplash.com/photo-1618216820397-29a1d1e6d4a4',
        'https://images.unsplash.com/photo-1615876210562-3dadae4b4f9d',
        'https://images.unsplash.com/photo-1522708323590-d24dbb6b0267',
        'https://images.unsplash.com/photo-1493809842364-78817add7ffb',
    ];

    /** @var list<string> */
    private const IMAGE_POOL_LAND = [
        'https://images.unsplash.com/photo-1500382017468-9049fed4ef57',
        'https://images.unsplash.com/photo-1500076656116-558359c3d734',
        'https://images.unsplash.com/photo-1501785888041-af3ef285b470',
        'https://images.unsplash.com/photo-1441974231531-c6227db76b6e',
        'https://images.unsplash.com/photo-1472223711764-12849cda2878',
        'https://images.unsplash.com/photo-1506905925346-21bda4d32df4',
        'https://images.unsplash.com/photo-1511497584788-876760111969',
        'https://images.unsplash.com/photo-1448375240586-882707db888b',
        'https://images.unsplash.com/photo-1475924156734-496f6cac6ec1',
        'https://images.unsplash.com/photo-1469474968028-56623f02e42e',
    ];

    /** @var list<string> */
    private const IMAGE_POOL_GARAGE = [
        'https://images.unsplash.com/photo-1486006920555-c77dcf18193c',
        'https://images.unsplash.com/photo-1590674899484-d5670efcb016',
        'https://images.unsplash.com/photo-1489515217757-5fd1be406fef',
        'https://images.unsplash.com/photo-1503376780353-7e6692767b70',
        'https://images.unsplash.com/photo-1492144534655-ae79c964c9d7',
        'https://images.unsplash.com/photo-1558618666-fcd25c85cd64',
        'https://images.unsplash.com/photo-1580273916550-e323be2ae537',
        'https://images.unsplash.com/photo-1533473359331-0135ef1b58bf',
    ];

    /** @var list<string> */
    private const IMAGE_POOL_PARKING = [
        'https://images.unsplash.com/photo-1506521781263-d8422e82d327',
        'https://images.unsplash.com/photo-1621905252507-b35492cc74b4',
        'https://images.unsplash.com/photo-1503376780353-7e6692767b70',
        'https://images.unsplash.com/photo-1494976388531-d1058494cdd8',
        'https://images.unsplash.com/photo-1552519507-da3b142c6e3d',
        'https://images.unsplash.com/photo-1549317661-bd32c8ce0db2',
        'https://images.unsplash.com/photo-1489824904134-891ab64532f1',
    ];

    /** @var list<string> */
    private const IMAGE_POOL_OFFICE = [
        'https://images.unsplash.com/photo-1497366216548-37526070297c',
        'https://images.unsplash.com/photo-1497366754035-f200968a6e72',
        'https://images.unsplash.com/photo-1497366811353-6870744d04b2',
        'https://images.unsplash.com/photo-1524758631624-e2822e304c36',
        'https://images.unsplash.com/photo-1604328698692-f76ea9498e76',
        'https://images.unsplash.com/photo-1600880292203-757bb62b4baf',
        'https://images.unsplash.com/photo-1568992687947-868a62a9f521',
        'https://images.unsplash.com/photo-1519389950473-47ba0277781c',
        'https://images.unsplash.com/photo-1522071820081-009f0129c71c',
    ];

    /** @var list<string> */
    private const IMAGE_POOL_RETAIL = [
        'https://images.unsplash.com/photo-1441986300917-64674bd600d8',
        'https://images.unsplash.com/photo-1604719312566-8912e9227c6a',
        'https://images.unsplash.com/photo-1555529669-e69e7aa0ba9a',
        'https://images.unsplash.com/photo-1441984919966-3496ed675f93',
        'https://images.unsplash.com/photo-1556740758-90de374c12ad',
        'https://images.unsplash.com/photo-1600880292203-757bb62b4baf',
        'https://images.unsplash.com/photo-1604719312566-8912e9227c6a',
        'https://images.unsplash.com/photo-1441986300917-64674bd600d8',
    ];

    /** @var list<string> */
    private const IMAGE_POOL_WAREHOUSE = [
        'https://images.unsplash.com/photo-1586528116311-ad8dd3c8310d',
        'https://images.unsplash.com/photo-1587293852726-70cdb56c2866',
        'https://images.unsplash.com/photo-1601584115197-04ecc0da31d7',
        'https://images.unsplash.com/photo-1600880292203-757bb62b4baf',
    ];

    public function __construct(
        private readonly EntityManagerInterface $em,
        private readonly ExchangeRateService $exchangeRateService,
        private readonly MetroProximityCalculator $metroProximityCalculator,
    ) {
        parent::__construct();
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);
        $conn = $this->em->getConnection();

        $ownerId = (int) $conn->fetchOne('SELECT id FROM users ORDER BY id ASC LIMIT 1');
        if ($ownerId <= 0) {
            $io->error('No users in database; create a user first.');

            return Command::FAILURE;
        }

        $io->section('Clearing existing listings and related rows');
        $this->truncatePropertyRelated($conn);

        $regions = $conn->fetchAllAssociative(
            'SELECT id FROM regions ORDER BY id ASC'
        );
        $owner = Id::fromInt($ownerId);
        $total = 0;

        foreach ($regions as $regionRow) {
            $regionId = (int) $regionRow['id'];
            if (!isset(self::REGION_CENTERS[$regionId])) {
                $io->warning(sprintf('Skip region id %d (no center coordinates in command).', $regionId));

                continue;
            }

            // Только областной центр (Минск, Брест, …), не сельские НП
            $cityCenterId = $conn->fetchOne(
                'SELECT c.id FROM cities c
                 INNER JOIN region_districts rd ON c.region_district_id = rd.id
                 WHERE rd.region_id = ? AND c.is_main = 1
                 ORDER BY c.id ASC
                 LIMIT 1',
                [$regionId]
            );
            if ($cityCenterId === false || $cityCenterId === null) {
                $cityCenterId = $conn->fetchOne(
                    'SELECT c.id FROM cities c
                     INNER JOIN region_districts rd ON c.region_district_id = rd.id
                     INNER JOIN regions r ON rd.region_id = r.id
                     WHERE r.id = ? AND c.slug = r.slug
                     LIMIT 1',
                    [$regionId]
                );
            }
            if ($cityCenterId === false || $cityCenterId === null) {
                $io->warning(sprintf('Region %d: не найден город-центр (is_main или slug области).', $regionId));

                continue;
            }

            $cityCenterId = (int) $cityCenterId;

            [$baseLat, $baseLon] = self::REGION_CENTERS[$regionId];
            $slot = 0;

            foreach (PropertyType::cases() as $typeCase) {
                $type = $typeCase->value;
                $listingCount = $type === PropertyType::Apartment->value
                    ? self::APARTMENT_LISTINGS_PER_CATEGORY
                    : self::LISTINGS_PER_CATEGORY;
                for ($i = 0; $i < $listingCount; ++$i) {
                    $spec = $this->buildSpecForPropertyCategory($type, $i, $regionId);
                    $cityId = $cityCenterId;
                    ++$slot;
                    $jitterLat = $baseLat + (($slot % 7) - 3) * 0.012;
                    $jitterLon = $baseLon + (($slot % 5) - 2) * 0.015;

                    $property = $this->makeProperty($owner, $cityId, $jitterLat, $jitterLon, $spec);
                    $property->setStatus('published');
                    $amount = $property->getPrice()->getAmount();
                    $currency = $property->getPrice()->getCurrency();
                    $property->setPriceByn($this->exchangeRateService->calculatePriceByn($amount, $currency));

                    $this->em->persist($property);
                    $this->em->flush();
                    $this->metroProximityCalculator->syncForProperty($property);
                    $this->em->flush();
                    ++$total;
                }
            }
        }

        $perRegion = self::LISTINGS_PER_CATEGORY * (\count(PropertyType::cases()) - 1)
            + self::APARTMENT_LISTINGS_PER_CATEGORY;
        $io->success(sprintf(
            'Created %d published properties (~%d per region; apartments %d, other types %d each).',
            $total,
            $perRegion,
            self::APARTMENT_LISTINGS_PER_CATEGORY,
            self::LISTINGS_PER_CATEGORY
        ));

        return Command::SUCCESS;
    }

    private function truncatePropertyRelated(Connection $conn): void
    {
        $conn->executeStatement('SET FOREIGN_KEY_CHECKS=0');
        $conn->executeStatement('DELETE FROM messages');
        $conn->executeStatement('DELETE FROM conversations');
        $conn->executeStatement('DELETE FROM favorites');
        $conn->executeStatement('DELETE FROM property_daily_stats');
        $conn->executeStatement('DELETE FROM property_metro_stations');
        $conn->executeStatement('DELETE FROM property_revisions');
        $conn->executeStatement('DELETE FROM properties');
        $conn->executeStatement('SET FOREIGN_KEY_CHECKS=1');
    }

    /**
     * @return list<string>
     */
    private function imagePoolForPropertyType(string $type): array
    {
        $base = match ($type) {
            PropertyType::Apartment->value => self::IMAGE_POOL_APARTMENT,
            PropertyType::House->value, PropertyType::Dacha->value => self::IMAGE_POOL_HOUSE,
            PropertyType::Room->value => self::IMAGE_POOL_ROOM,
            PropertyType::Land->value => self::IMAGE_POOL_LAND,
            PropertyType::Garage->value => self::IMAGE_POOL_GARAGE,
            PropertyType::Parking->value => self::IMAGE_POOL_PARKING,
            PropertyType::Office->value => self::IMAGE_POOL_OFFICE,
            PropertyType::Retail->value => self::IMAGE_POOL_RETAIL,
            PropertyType::Warehouse->value => self::IMAGE_POOL_WAREHOUSE,
            default => self::IMAGE_POOL_APARTMENT,
        };

        return array_map(
            static fn (string $u): string => $u . self::UNSPLASH,
            $base,
        );
    }

    /**
     * Случайный набор 4–6 фото; порядок перемешан — первая картинка становится обложкой.
     *
     * @return list<string>
     */
    private function randomImagesForPropertyType(string $type): array
    {
        $pool = array_values(array_unique($this->imagePoolForPropertyType($type)));
        if ($pool === []) {
            return ['https://images.unsplash.com/photo-1600596542815-ffad4c1539a9' . self::UNSPLASH];
        }

        $copy = $pool;
        shuffle($copy);
        $n = min(random_int(4, 6), count($copy));

        return array_slice($copy, 0, $n);
    }

    private function resolveDealType(string $type, int $index): string
    {
        if ($type === PropertyType::Apartment->value) {
            if ($index < 11) {
                return DealType::Sale->value;
            }
            if ($index < 13) {
                return DealType::Rent->value;
            }

            return DealType::Daily->value;
        }

        if ($type === PropertyType::Land->value) {
            return DealType::Sale->value;
        }

        if ($type === PropertyType::Room->value) {
            return $index % 2 === 0 ? DealType::Sale->value : DealType::Rent->value;
        }

        if (\in_array($type, [
            PropertyType::Garage->value,
            PropertyType::Parking->value,
            PropertyType::Office->value,
            PropertyType::Retail->value,
            PropertyType::Warehouse->value,
        ], true)) {
            return $index % 2 === 0 ? DealType::Sale->value : DealType::Rent->value;
        }

        return match ($index % 3) {
            0 => DealType::Sale->value,
            1 => DealType::Rent->value,
            default => DealType::Daily->value,
        };
    }

    private function dealConditionFor(string $dealType, string $propertyType, int $index): ?array
    {
        if ($dealType === DealType::Daily->value) {
            return null;
        }

        if ($dealType === DealType::Sale->value) {
            $opts = ['Чистая продажа', 'Подбираются варианты', 'Обмен'];

            return [$opts[$index % \count($opts)]];
        }

        $opts = ['Чистая аренда', 'Подбираются варианты', 'Предоплата 1 мес.', 'Предоплата 3 мес.'];
        $filtered = \array_values(\array_filter(
            $opts,
            static function (string $c) use ($propertyType): bool {
                if (\in_array($propertyType, [PropertyType::Garage->value, PropertyType::Parking->value], true)) {
                    return $c !== 'Подбираются варианты';
                }

                return true;
            }
        ));

        return [$filtered[$index % \count($filtered)]];
    }

    /**
     * @return array<string, mixed>
     */
    private function buildSpecForPropertyCategory(string $type, int $index, int $regionId): array
    {
        $dealType = $this->resolveDealType($type, $index);
        $images = $this->randomImagesForPropertyType($type);
        $deal = $this->dealConditionFor($dealType, $type, $index + $regionId);
        $suffix = sprintf(' — вариант %d', $index + 1);

        $priceBump = ($regionId % 5) * 3_000_00 + $index * 2_500_00;

        return match ($type) {
            PropertyType::Apartment->value => $this->buildApartmentSpec($dealType, $index, $images, $deal, $suffix, $priceBump),
            PropertyType::House->value => $this->buildHouseSpec($dealType, $index, $images, $deal, $suffix, $priceBump),
            PropertyType::Room->value => $this->buildRoomSpec($dealType, $index, $images, $deal, $suffix, $priceBump),
            PropertyType::Land->value => $this->buildLandSpec($index, $images, $deal, $suffix, $priceBump),
            PropertyType::Garage->value => $this->buildGarageSpec($dealType, $index, $images, $deal, $suffix, $priceBump),
            PropertyType::Parking->value => $this->buildParkingSpec($dealType, $index, $images, $deal, $suffix, $priceBump),
            PropertyType::Dacha->value => $this->buildDachaSpec($dealType, $index, $images, $deal, $suffix, $priceBump),
            PropertyType::Office->value => $this->buildOfficeSpec($dealType, $index, $images, $deal, $suffix, $priceBump),
            PropertyType::Retail->value => $this->buildRetailSpec($dealType, $index, $images, $deal, $suffix, $priceBump),
            PropertyType::Warehouse->value => $this->buildWarehouseSpec($dealType, $index, $images, $deal, $suffix, $priceBump),
            default => throw new \LogicException('Unsupported property type: ' . $type),
        };
    }

    /**
     * @param list<string> $images
     * @param string[]|null $deal
     * @return array<string, mixed>
     */
    private function buildApartmentSpec(
        string $dealType,
        int $index,
        array $images,
        ?array $deal,
        string $suffix,
        int $priceBump,
    ): array {
        $rooms = 1 + ($index % 4);
        $area = 32.0 + $index * 2.5 + ($rooms * 4);
        $floor = 2 + ($index % 12);
        $total = max($floor + 1, 5 + ($index % 10));
        $title = match ($rooms) {
            1 => 'Однокомнатная квартира' . $suffix,
            2 => 'Двухкомнатная квартира' . $suffix,
            3 => 'Трёхкомнатная квартира' . $suffix,
            default => 'Четырёхкомнатная квартира' . $suffix,
        };

        $base = match ($dealType) {
            DealType::Sale->value => 165_000_00 + $index * 12_000_00,
            DealType::Rent->value => 38_000_00 + $index * 2_000_00,
            default => 11_000_00 + $index * 500_00,
        };

        if ($dealType === DealType::Daily->value) {
            return $this->spec(
                PropertyType::Apartment->value,
                $dealType,
                $title,
                $base + $priceBump,
                $area,
                null,
                max(1, $rooms),
                $floor,
                $total,
                $images,
                null,
                2 + ($index % 4),
                2 + ($index % 3),
                '14:00',
                '12:00',
            );
        }

        return $this->spec(
            PropertyType::Apartment->value,
            $dealType,
            $title,
            $base + $priceBump,
            $area,
            null,
            max(1, $rooms),
            $floor,
            $total,
            $images,
            $deal,
        );
    }

    /**
     * @param list<string> $images
     * @param string[]|null $deal
     * @return array<string, mixed>
     */
    private function buildHouseSpec(
        string $dealType,
        int $index,
        array $images,
        ?array $deal,
        string $suffix,
        int $priceBump,
    ): array {
        $rooms = 3 + ($index % 4);
        $land = 7.0 + ($index % 10) * 0.45;
        $area = 95.0 + $index * 6.0;
        $title = 'Дом с участком' . $suffix;

        $base = match ($dealType) {
            DealType::Sale->value => 280_000_00 + $index * 25_000_00,
            DealType::Rent->value => 72_000_00 + $index * 3_000_00,
            default => 25_000_00 + $index * 1_000_00,
        };

        if ($dealType === DealType::Daily->value) {
            return $this->spec(
                PropertyType::House->value,
                $dealType,
                $title,
                $base + $priceBump,
                $area,
                $land,
                $rooms,
                null,
                null,
                $images,
                null,
                5 + ($index % 4),
                5 + ($index % 3),
                '15:00',
                '11:00',
            );
        }

        return $this->spec(
            PropertyType::House->value,
            $dealType,
            $title,
            $base + $priceBump,
            $area,
            $land,
            $rooms,
            null,
            null,
            $images,
            $deal,
        );
    }

    /**
     * @param list<string> $images
     * @param string[]|null $deal
     * @return array<string, mixed>
     */
    private function buildRoomSpec(
        string $dealType,
        int $index,
        array $images,
        ?array $deal,
        string $suffix,
        int $priceBump,
    ): array {
        $inDeal = 1 + ($index % 4);
        $rArea = 12.0 + $index * 0.6;
        $floor = 2 + ($index % 8);
        $total = max($floor + 1, 5 + ($index % 6));
        $title = 'Комната в общей квартире' . $suffix;
        $base = $dealType === DealType::Sale->value
            ? 32_000_00 + $index * 3_000_00
            : 18_000_00 + $index * 1_500_00;

        return $this->spec(
            PropertyType::Room->value,
            $dealType,
            $title,
            $base + $priceBump,
            $rArea,
            null,
            null,
            $floor,
            $total,
            $images,
            $deal,
            null,
            null,
            null,
            null,
            $inDeal,
            $rArea,
        );
    }

    /**
     * @param list<string> $images
     * @param string[]|null $deal
     * @return array<string, mixed>
     */
    private function buildLandSpec(
        int $index,
        array $images,
        ?array $deal,
        string $suffix,
        int $priceBump,
    ): array {
        $sotki = 6.0 + $index * 0.55;
        $title = 'Земельный участок' . $suffix;
        $base = 62_000_00 + $index * 8_000_00;

        return $this->spec(
            PropertyType::Land->value,
            DealType::Sale->value,
            $title,
            $base + $priceBump,
            0.0,
            $sotki,
            null,
            null,
            null,
            $images,
            $deal,
        );
    }

    /**
     * @param list<string> $images
     * @param string[]|null $deal
     * @return array<string, mixed>
     */
    private function buildGarageSpec(
        string $dealType,
        int $index,
        array $images,
        ?array $deal,
        string $suffix,
        int $priceBump,
    ): array {
        $area = 16.0 + ($index % 8) * 2.0;
        $title = 'Гараж / бокс' . $suffix;
        $base = $dealType === DealType::Sale->value
            ? 14_000_00 + $index * 2_000_00
            : 7_000_00 + $index * 500_00;

        return $this->spec(
            PropertyType::Garage->value,
            $dealType,
            $title,
            $base + $priceBump,
            $area,
            null,
            null,
            null,
            null,
            $images,
            $deal,
        );
    }

    /**
     * @param list<string> $images
     * @param string[]|null $deal
     * @return array<string, mixed>
     */
    private function buildParkingSpec(
        string $dealType,
        int $index,
        array $images,
        ?array $deal,
        string $suffix,
        int $priceBump,
    ): array {
        $area = 11.0 + ($index % 6) * 1.2;
        $title = 'Машиноместо' . $suffix;
        $base = $dealType === DealType::Sale->value
            ? 26_000_00 + $index * 3_000_00
            : 5_500_00 + $index * 400_00;

        return $this->spec(
            PropertyType::Parking->value,
            $dealType,
            $title,
            $base + $priceBump,
            $area,
            null,
            null,
            null,
            null,
            $images,
            $deal,
        );
    }

    /**
     * @param list<string> $images
     * @param string[]|null $deal
     * @return array<string, mixed>
     */
    private function buildDachaSpec(
        string $dealType,
        int $index,
        array $images,
        ?array $deal,
        string $suffix,
        int $priceBump,
    ): array {
        $land = 5.5 + ($index % 12) * 0.35;
        $area = 38.0 + $index * 2.0;
        $rooms = 2 + ($index % 3);
        $title = 'Дача' . $suffix;

        $base = match ($dealType) {
            DealType::Sale->value => 58_000_00 + $index * 5_000_00,
            DealType::Rent->value => 15_000_00 + $index * 1_200_00,
            default => 9_500_00 + $index * 600_00,
        };

        if ($dealType === DealType::Daily->value) {
            return $this->spec(
                PropertyType::Dacha->value,
                $dealType,
                $title,
                $base + $priceBump,
                $area,
                $land,
                $rooms,
                null,
                null,
                $images,
                null,
                4 + ($index % 3),
                3 + ($index % 2),
                '14:00',
                '12:00',
            );
        }

        return $this->spec(
            PropertyType::Dacha->value,
            $dealType,
            $title,
            $base + $priceBump,
            $area,
            $land,
            $rooms,
            null,
            null,
            $images,
            $deal,
        );
    }

    /**
     * @param list<string> $images
     * @param string[]|null $deal
     * @return array<string, mixed>
     */
    private function buildOfficeSpec(
        string $dealType,
        int $index,
        array $images,
        ?array $deal,
        string $suffix,
        int $priceBump,
    ): array {
        $area = 55.0 + $index * 7.0;
        $floor = 1 + ($index % 10);
        $total = max($floor + 2, 6 + ($index % 5));
        $title = 'Офисное помещение' . $suffix;
        $base = $dealType === DealType::Sale->value
            ? 175_000_00 + $index * 18_000_00
            : 48_000_00 + $index * 3_500_00;

        return $this->spec(
            PropertyType::Office->value,
            $dealType,
            $title,
            $base + $priceBump,
            $area,
            null,
            null,
            $floor,
            $total,
            $images,
            $deal,
        );
    }

    /**
     * @param list<string> $images
     * @param string[]|null $deal
     * @return array<string, mixed>
     */
    private function buildRetailSpec(
        string $dealType,
        int $index,
        array $images,
        ?array $deal,
        string $suffix,
        int $priceBump,
    ): array {
        $area = 42.0 + $index * 5.5;
        $floor = 1 + ($index % 3);
        $total = max(3, $floor + 2);
        $title = 'Торговое помещение' . $suffix;
        $base = $dealType === DealType::Sale->value
            ? 240_000_00 + $index * 22_000_00
            : 42_000_00 + $index * 3_000_00;

        return $this->spec(
            PropertyType::Retail->value,
            $dealType,
            $title,
            $base + $priceBump,
            $area,
            null,
            null,
            $floor,
            $total,
            $images,
            $deal,
        );
    }

    /**
     * @param list<string> $images
     * @param string[]|null $deal
     * @return array<string, mixed>
     */
    private function buildWarehouseSpec(
        string $dealType,
        int $index,
        array $images,
        ?array $deal,
        string $suffix,
        int $priceBump,
    ): array {
        $area = 180.0 + $index * 28.0;
        $title = 'Склад / производство' . $suffix;
        $base = $dealType === DealType::Sale->value
            ? 350_000_00 + $index * 40_000_00
            : 32_000_00 + $index * 4_000_00;

        return $this->spec(
            PropertyType::Warehouse->value,
            $dealType,
            $title,
            $base + $priceBump,
            $area,
            null,
            null,
            1,
            1,
            $images,
            $deal,
        );
    }

    /**
     * @param string[] $images
     * @param string[]|null $dealOne
     * @return array<string, mixed>
     */
    private function spec(
        string $type,
        string $dealType,
        string $title,
        int $priceKopecks,
        float $area,
        ?float $landArea,
        ?int $rooms,
        ?int $floor,
        ?int $totalFloors,
        array $images,
        ?array $dealOne = null,
        ?int $maxGuests = null,
        ?int $beds = null,
        ?string $checkIn = null,
        ?string $checkOut = null,
        ?int $roomsInDeal = null,
        ?float $roomsArea = null,
    ): array {
        return [
            'type' => $type,
            'dealType' => $dealType,
            'title' => $title,
            'priceKopecks' => $priceKopecks,
            'area' => $area,
            'landArea' => $landArea,
            'rooms' => $rooms,
            'floor' => $floor,
            'totalFloors' => $totalFloors,
            'images' => $images,
            'dealConditions' => $dealOne,
            'maxDailyGuests' => $maxGuests,
            'dailySingleBeds' => $beds,
            'dailyDoubleBeds' => 0,
            'checkInTime' => $checkIn,
            'checkOutTime' => $checkOut,
            'roomsInDeal' => $roomsInDeal,
            'roomsArea' => $roomsArea,
        ];
    }

    /**
     * @param array<string, mixed> $spec
     */
    private function makeProperty(Id $ownerId, int $cityId, float $lat, float $lon, array $spec): Property
    {
        $type = $spec['type'];
        $dealType = $spec['dealType'];
        $desc = sprintf(
            'Демонстрационное объявление для тестирования каталога. %s. Подробности по запросу.',
            $spec['title']
        );

        $bathrooms = \in_array($type, [PropertyType::Apartment->value, PropertyType::House->value, PropertyType::Dacha->value], true) ? 1 : null;
        $yearBuilt = \in_array($type, [PropertyType::Apartment->value, PropertyType::House->value], true) ? 2012 : null;
        $renovation = $type === PropertyType::Apartment->value ? 'cosmetic' : null;
        $balcony = $type === PropertyType::Apartment->value ? 'yes' : null;
        $livingArea = $type === PropertyType::Apartment->value ? round($spec['area'] * 0.72, 1) : null;
        $kitchenArea = $type === PropertyType::Apartment->value ? round($spec['area'] * 0.18, 1) : null;

        return new Property(
            ownerId: $ownerId,
            type: $type,
            dealType: $dealType,
            title: $spec['title'],
            description: $desc,
            price: Price::fromAmount($spec['priceKopecks'], 'BYN'),
            area: $spec['area'],
            rooms: $spec['rooms'],
            floor: $spec['floor'],
            totalFloors: $spec['totalFloors'],
            bathrooms: $bathrooms,
            yearBuilt: $yearBuilt,
            renovation: $renovation,
            balcony: $balcony,
            livingArea: $livingArea,
            kitchenArea: $kitchenArea,
            dealConditions: $spec['dealConditions'],
            maxDailyGuests: $spec['maxDailyGuests'],
            dailySingleBeds: $spec['dailySingleBeds'],
            dailyDoubleBeds: $spec['dailyDoubleBeds'],
            checkInTime: $spec['checkInTime'],
            checkOutTime: $spec['checkOutTime'],
            address: Address::create((string) (random_int(1, 120)), random_int(1, 2) === 1 ? (string) random_int(1, 9) : null),
            cityId: $cityId,
            coordinates: Coordinates::create($lat, $lon),
            landArea: $spec['landArea'],
            streetId: null,
            images: $spec['images'],
            amenities: ['furniture', 'appliances'],
            status: 'draft',
            contactPhone: null,
            contactName: null,
            roomsInDeal: $spec['roomsInDeal'],
            roomsArea: $spec['roomsArea'],
        );
    }
}
