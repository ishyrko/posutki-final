<?php

declare(strict_types=1);

namespace App\Infrastructure\Symfony\Command;

use App\Application\Service\PropertyPlacementService;
use App\Domain\Property\Entity\Property;
use App\Domain\Property\Enum\DealType;
use App\Domain\Property\Enum\PropertyType;
use App\Domain\Property\ValueObject\Address;
use App\Domain\Property\ValueObject\Coordinates;
use App\Domain\Property\ValueObject\Price;
use App\Domain\Shared\ValueObject\Id;
use App\Infrastructure\Service\ExchangeRateService;
use App\Infrastructure\Service\MarketplaceDataPurger;
use App\Infrastructure\Service\MetroProximityCalculator;
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
        private readonly MarketplaceDataPurger $marketplaceDataPurger,
        private readonly ExchangeRateService $exchangeRateService,
        private readonly MetroProximityCalculator $metroProximityCalculator,
        private readonly PropertyPlacementService $placementService,
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
        $this->marketplaceDataPurger->purgeProperties($conn);
        // Demo owner gets a fresh one-time free VIP 1 for the first seeded listing.
        $conn->executeStatement(
            'UPDATE users SET has_used_free_placement_trial = 0 WHERE id = ?',
            [$ownerId],
        );
        $this->em->clear();

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
                    $grantFreeTrial = $this->placementService->shouldGrantFreeTrial($property);
                    $property->setStatus('published', $grantFreeTrial);
                    $amount = $property->getPrice()->getAmount();
                    $currency = $property->getPrice()->getCurrency();
                    $property->setPriceByn($this->exchangeRateService->calculatePriceByn($amount, $currency));

                    $this->em->persist($property);
                    $this->em->flush();
                    if ($grantFreeTrial) {
                        $this->placementService->markFreePlacementTrialUsed($property);
                    }
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

        return match ($type) {
            PropertyType::Apartment->value => $this->buildApartmentSpec($index, $regionId, $images, $suffix),
            PropertyType::House->value => $this->buildHouseSpec($index, $regionId, $images, $suffix),
            default => throw new \LogicException('Unsupported property type: ' . $type),
        };
    }

    private const RENOVATION_OPTIONS = ['Косметический', 'Хороший', 'Евроремонт', 'Дизайнерский'];

    private const BALCONY_OPTIONS = ['Балкон', 'Лоджия', 'Балкон и лоджия', 'Нет'];

    private const CHECKIN_TIMES = ['13:00', '14:00', '15:00', '16:00'];

    private const CHECKOUT_TIMES = ['10:00', '11:00', '12:00'];

    /** @var list<list<string>> */
    private const PAYMENT_METHOD_OPTIONS = [
        ['cash', 'card'],
        ['cash', 'bank_transfer'],
        ['card', 'bank_transfer'],
        ['cash', 'card', 'bank_transfer'],
    ];

    /** @var list<string> Базовые удобства квартиры */
    private const AMENITIES_APT_BASE = [
        'wifi', 'fridge', 'electric_stove', 'microwave', 'kettle',
        'dishes_utensils', 'washing_machine',
    ];

    /** @var list<string> Удобства квартиры среднего уровня */
    private const AMENITIES_APT_MID = [
        'smart_tv', 'air_conditioner', 'iron', 'hairdryer',
        'bathroom_combined', 'toiletries', 'towels',
    ];

    /** @var list<string> Премиум-удобства квартиры */
    private const AMENITIES_APT_PREMIUM = [
        'coffee_machine', 'dishwasher', 'rain_shower', 'bluetooth_speaker',
        'robot_vacuum', 'dryer', 'projector',
    ];

    /** @var list<string> Базовые удобства дома */
    private const AMENITIES_HOUSE_BASE = [
        'wifi', 'fridge', 'electric_stove', 'microwave', 'kettle',
        'dishes_utensils', 'washing_machine', 'parking_open', 'cctv',
    ];

    /** @var list<string> Удобства дома среднего уровня */
    private const AMENITIES_HOUSE_MID = [
        'smart_tv', 'air_conditioner', 'iron', 'bbq', 'gazebo', 'towels',
    ];

    /** @var list<string> Премиум-удобства дома */
    private const AMENITIES_HOUSE_PREMIUM = [
        'coffee_machine', 'dishwasher', 'sauna', 'pool', 'playground',
        'garden', 'parking_covered', 'bathrobes',
    ];

    private const APARTMENT_DESCRIPTIONS = [
        1 => [
            'Уютная однокомнатная квартира в центре города. Всё необходимое для комфортного проживания: современная мебель, оборудованная кухня, стабильный Wi-Fi.',
            'Светлая студия с видом на город. Идеальный вариант для командировок и коротких поездок. Рядом кафе, магазины, остановки общественного транспорта.',
            'Тихая квартира в жилом районе, 5 минут пешком до центра. Свежий ремонт, новая техника, всё для самостоятельного отдыха.',
        ],
        2 => [
            'Двухкомнатная квартира для семьи или компании. Просторная гостиная, отдельная спальня, полностью оснащённая кухня. Бесплатный паркинг во дворе.',
            'Стильная «двушка» с дизайнерским ремонтом. Кондиционер, Smart TV, скоростной интернет. Всё для комфортного отдыха.',
            'Уютная квартира в тихом дворе. Две отдельные комнаты, просторная кухня-гостиная. Подходит для длительного проживания.',
        ],
        3 => [
            'Просторная трёхкомнатная квартира для большой компании или семьи. Три отдельные спальни, большая гостиная с диваном, две ванные комнаты.',
            'Современная квартира с панорамным видом. Три спальни, гардеробная, оборудованная кухня с техникой. Высокий этаж, тихий район.',
            'Квартира бизнес-класса в центре. Три комнаты, дизайнерская отделка, умная техника. Рядом рестораны, торговые центры.',
        ],
        4 => [
            'Апартаменты премиум-класса на несколько семей или большой компании. Четыре спальни, две ванные, просторная гостиная с кухней-островом.',
            'Просторные апартаменты с террасой. Четыре отдельные комнаты, два санузла, барная стойка. Идеально для корпоративного отдыха или семейного торжества.',
            'Люкс-апартаменты в новостройке. Дизайнерский ремонт, smart home, четыре спальни, кинотеатр. Для самых взыскательных гостей.',
        ],
    ];

    private const HOUSE_DESCRIPTIONS = [
        'Загородный дом для отдыха всей семьёй. Просторные комнаты, оборудованная кухня, зона отдыха во дворе. Свежий воздух, тишина и уют.',
        'Уютный дом с ухоженным участком. Беседка с мангалом, летняя зона отдыха, парковка на несколько автомобилей. Идеально для праздника или длительного отдыха.',
        'Современный коттедж с баней и бассейном. Несколько спален, просторная гостиная с камином, полностью оснащённая кухня. Незабываемый отдых на природе.',
        'Дом в экологически чистом районе. Большой участок с садом и огородом, беседка, мангальная зона. Подходит для семей с детьми.',
        'Кирпичный дом с панорамным видом. Несколько комнат, два санузла, гараж на две машины. Уютная терраса для вечерних посиделок.',
    ];

    /** Посуточная цена в BYN (как в форме объявления: целые рубли за сутки). */
    private function dailyPriceAmount(int $index, int $regionId, string $type, ?int $rooms = null): int
    {
        $regionOffset = ($regionId % 5) * 6;
        $indexOffset = ($index % 7) * 5;

        if ($type === PropertyType::House->value) {
            return min(300, 105 + $regionOffset + $indexOffset + ($index % 4) * 22);
        }

        $rooms = $rooms ?? 1;
        $baseByRooms = match ($rooms) {
            1 => 50,
            2 => 72,
            3 => 98,
            default => 125,
        };

        return min(300, $baseByRooms + $regionOffset + $indexOffset + ($index % 5) * 8);
    }

    /**
     * @param list<string> $images
     * @return array<string, mixed>
     */
    private function buildApartmentSpec(
        int $index,
        int $regionId,
        array $images,
        string $suffix,
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
        $tier = $index % 3;
        $guests = 2 + ($index % 4);
        $singleBeds = $rooms > 2 ? $rooms : ($index % 2 === 0 ? $rooms : 0);
        $doubleBeds = $rooms <= 2 ? 1 : ($index % 2);
        $checkIn = self::CHECKIN_TIMES[$index % count(self::CHECKIN_TIMES)];
        $checkOut = self::CHECKOUT_TIMES[$index % count(self::CHECKOUT_TIMES)];
        $dealConditions = match ($index % 4) {
            1 => ['contactless_checkin'],
            2 => ['24h_checkin'],
            3 => ['contactless_checkin', '24h_checkin'],
            default => null,
        };
        $descPool = self::APARTMENT_DESCRIPTIONS[$rooms] ?? self::APARTMENT_DESCRIPTIONS[1];
        $desc = $descPool[$index % count($descPool)];
        $amenities = match ($tier) {
            0 => self::AMENITIES_APT_BASE,
            1 => array_merge(self::AMENITIES_APT_BASE, self::AMENITIES_APT_MID),
            default => array_merge(self::AMENITIES_APT_BASE, self::AMENITIES_APT_MID, self::AMENITIES_APT_PREMIUM),
        };

        return $this->spec(
            PropertyType::Apartment->value,
            DealType::Daily->value,
            $title,
            $this->dailyPriceAmount($index, $regionId, PropertyType::Apartment->value, $rooms),
            $area,
            null,
            max(1, $rooms),
            $floor,
            $total,
            $images,
            $dealConditions,
            $guests,
            $singleBeds,
            $checkIn,
            $checkOut,
            null,
            null,
            $amenities,
            $desc,
            $doubleBeds,
            $index,
        );
    }

    /**
     * @param list<string> $images
     * @return array<string, mixed>
     */
    private function buildHouseSpec(
        int $index,
        int $regionId,
        array $images,
        string $suffix,
    ): array {
        $rooms = 3 + ($index % 4);
        $land = 7.0 + ($index % 10) * 0.45;
        $area = 95.0 + $index * 6.0;
        $title = 'Дом с участком' . $suffix;
        $tier = $index % 3;
        $guests = 5 + ($index % 4);
        $singleBeds = $rooms;
        $doubleBeds = 1 + ($index % 2);
        $checkIn = self::CHECKIN_TIMES[($index + 1) % count(self::CHECKIN_TIMES)];
        $checkOut = self::CHECKOUT_TIMES[($index + 1) % count(self::CHECKOUT_TIMES)];
        $dealConditions = match ($index % 3) {
            1 => ['contactless_checkin'],
            2 => ['24h_checkin'],
            default => null,
        };
        $desc = self::HOUSE_DESCRIPTIONS[$index % count(self::HOUSE_DESCRIPTIONS)];
        $amenities = match ($tier) {
            0 => self::AMENITIES_HOUSE_BASE,
            1 => array_merge(self::AMENITIES_HOUSE_BASE, self::AMENITIES_HOUSE_MID),
            default => array_merge(self::AMENITIES_HOUSE_BASE, self::AMENITIES_HOUSE_MID, self::AMENITIES_HOUSE_PREMIUM),
        };

        return $this->spec(
            PropertyType::House->value,
            DealType::Daily->value,
            $title,
            $this->dailyPriceAmount($index, $regionId, PropertyType::House->value),
            $area,
            $land,
            $rooms,
            null,
            null,
            $images,
            $dealConditions,
            $guests,
            $singleBeds,
            $checkIn,
            $checkOut,
            null,
            null,
            $amenities,
            $desc,
            $doubleBeds,
            $index,
        );
    }

    /**
     * @param string[] $images
     * @param string[]|null $dealConditions
     * @param list<string> $amenities
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
        ?array $dealConditions = null,
        ?int $maxGuests = null,
        ?int $singleBeds = null,
        ?string $checkIn = null,
        ?string $checkOut = null,
        ?int $roomsInDeal = null,
        ?float $roomsArea = null,
        array $amenities = [],
        string $description = '',
        int $doubleBeds = 0,
        int $index = 0,
    ): array {
        return [
            'type' => $type,
            'dealType' => $dealType,
            'title' => $title,
            'description' => $description,
            'priceKopecks' => $priceKopecks,
            'area' => $area,
            'landArea' => $landArea,
            'rooms' => $rooms,
            'floor' => $floor,
            'totalFloors' => $totalFloors,
            'images' => $images,
            'dealConditions' => $dealConditions,
            'maxDailyGuests' => $maxGuests,
            'dailySingleBeds' => $singleBeds,
            'dailyDoubleBeds' => $doubleBeds,
            'checkInTime' => $checkIn,
            'checkOutTime' => $checkOut,
            'roomsInDeal' => $roomsInDeal,
            'roomsArea' => $roomsArea,
            'amenities' => $amenities,
            'index' => $index,
        ];
    }

    /**
     * @param array<string, mixed> $spec
     */
    private function makeProperty(Id $ownerId, int $cityId, float $lat, float $lon, array $spec): Property
    {
        $type = $spec['type'];
        $dealType = $spec['dealType'];
        $description = (string) ($spec['description'] ?: sprintf(
            'Демонстрационное объявление для тестирования каталога. %s. Подробности по запросу.',
            $spec['title']
        ));

        $index = $spec['index'] ?? 0;
        $bathrooms = $type === PropertyType::Apartment->value
            ? (($index % 3 === 2) ? 2 : 1)
            : (int) min(2, 1 + (int) ($spec['rooms'] ?? 3) / 4);
        $yearBuilt = $type === PropertyType::House->value
            ? (2005 + ($index % 15))
            : (2008 + ($index % 16));
        $renovation = $type === PropertyType::Apartment->value
            ? self::RENOVATION_OPTIONS[$index % count(self::RENOVATION_OPTIONS)]
            : null;
        $balcony = $type === PropertyType::Apartment->value
            ? self::BALCONY_OPTIONS[$index % count(self::BALCONY_OPTIONS)]
            : null;
        $livingArea = $type === PropertyType::Apartment->value ? round($spec['area'] * 0.72, 1) : null;
        $kitchenArea = $type === PropertyType::Apartment->value ? round($spec['area'] * 0.18, 1) : null;

        return new Property(
            ownerId: $ownerId,
            type: $type,
            dealType: $dealType,
            title: $spec['title'],
            description: $description,
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
            paymentMethods: self::PAYMENT_METHOD_OPTIONS[$index % count(self::PAYMENT_METHOD_OPTIONS)],
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
            amenities: $spec['amenities'] ?? [],
            status: 'draft',
            contactPhone: null,
            contactName: null,
            roomsInDeal: $spec['roomsInDeal'],
            roomsArea: $spec['roomsArea'],
        );
    }
}
