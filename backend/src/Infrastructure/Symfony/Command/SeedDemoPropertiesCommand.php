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
 * Удаляет объявления и создаёт демо: только посуточная аренда квартир и домов по областям.
 */
#[AsCommand(
    name: 'app:seed-demo-properties',
    description: 'Remove all properties and seed daily apartments & houses per region',
)]
final class SeedDemoPropertiesCommand extends Command
{
    private const LISTINGS_PER_CATEGORY = 12;

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
        'https://images.unsplash.com/photo-1600607688969-a5bfcd646154',
        'https://images.unsplash.com/photo-1600585154526-990dced4db0d',
        'https://images.unsplash.com/photo-1600566753190-17f0baa2a6c3',
        'https://images.unsplash.com/photo-1600210492493-0946911123ea',
    ];

    /** @var list<PropertyType> */
    private const SEED_PROPERTY_TYPES = [PropertyType::Apartment, PropertyType::House];

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

            foreach (self::SEED_PROPERTY_TYPES as $typeCase) {
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

        $perRegion = self::LISTINGS_PER_CATEGORY + self::APARTMENT_LISTINGS_PER_CATEGORY;
        $io->success(sprintf(
            'Created %d published properties (~%d per region: %d apartments + %d houses, all daily).',
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
            PropertyType::House->value => self::IMAGE_POOL_HOUSE,
            default => self::IMAGE_POOL_APARTMENT,
        };

        return array_map(
            static fn (string $u): string => $u . self::UNSPLASH,
            $base,
        );
    }

    /**
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

    /**
     * @return array<string, mixed>
     */
    private function buildSpecForPropertyCategory(string $type, int $index, int $regionId): array
    {
        $dealType = DealType::Daily->value;
        $images = $this->randomImagesForPropertyType($type);
        $suffix = sprintf(' — вариант %d', $index + 1);
        $priceBump = ($regionId % 5) * 3_000_00 + $index * 2_500_00;

        return match ($type) {
            PropertyType::Apartment->value => $this->buildApartmentSpec($index, $images, $suffix, $priceBump),
            PropertyType::House->value => $this->buildHouseSpec($index, $images, $suffix, $priceBump),
            default => throw new \LogicException('Unsupported property type: ' . $type),
        };
    }

    /**
     * @param list<string> $images
     * @return array<string, mixed>
     */
    private function buildApartmentSpec(
        int $index,
        array $images,
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

        $base = 11_000_00 + $index * 500_00;

        return $this->spec(
            PropertyType::Apartment->value,
            DealType::Daily->value,
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

    /**
     * @param list<string> $images
     * @return array<string, mixed>
     */
    private function buildHouseSpec(
        int $index,
        array $images,
        string $suffix,
        int $priceBump,
    ): array {
        $rooms = 3 + ($index % 4);
        $land = 7.0 + ($index % 10) * 0.45;
        $area = 95.0 + $index * 6.0;
        $title = 'Дом с участком' . $suffix;
        $base = 25_000_00 + $index * 1_000_00;

        return $this->spec(
            PropertyType::House->value,
            DealType::Daily->value,
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

        $bathrooms = 1;
        $yearBuilt = $type === PropertyType::House->value ? 2012 : 2012;
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
            address: Address::create((string) random_int(1, 120), random_int(1, 2) === 1 ? (string) random_int(1, 9) : null),
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
