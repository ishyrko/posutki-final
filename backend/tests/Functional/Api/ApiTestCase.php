<?php

declare(strict_types=1);

namespace App\Tests\Functional\Api;

use App\Domain\Property\Entity\City;
use App\Domain\Property\Entity\MetroStation;
use App\Domain\Property\Entity\Property;
use App\Domain\Property\Entity\PropertyMetroStation;
use App\Domain\Property\Entity\Region;
use App\Domain\Property\Entity\RegionDistrict;
use App\Infrastructure\Service\ExchangeRateService;
use App\Domain\Property\ValueObject\Address;
use App\Domain\Property\ValueObject\Coordinates;
use App\Domain\Property\ValueObject\Price;
use App\Domain\User\Entity\User;
use App\Domain\User\ValueObject\Email;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\Tools\SchemaTool;
use Symfony\Bundle\FrameworkBundle\KernelBrowser;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;

abstract class ApiTestCase extends WebTestCase
{
    protected KernelBrowser $client;

    protected function setUp(): void
    {
        parent::setUp();
        $this->client = static::createClient();
        $this->resetDatabase();
    }

    protected function createUser(string $email = 'user@example.com', string $plainPassword = 'Password123!'): User
    {
        $user = User::register(
            Email::fromString($email),
            '',
            'Test',
            'User'
        );

        /** @var UserPasswordHasherInterface $hasher */
        $hasher = static::getContainer()->get(UserPasswordHasherInterface::class);
        $hashedPassword = $hasher->hashPassword($user, $plainPassword);

        $passwordReflection = new \ReflectionProperty($user, 'password');
        $passwordReflection->setAccessible(true);
        $passwordReflection->setValue($user, $hashedPassword);

        $user->verify();

        $this->entityManager()->persist($user);
        $this->entityManager()->flush();

        return $user;
    }

    protected function createCity(
        string $name = 'Minsk',
        string $slug = 'minsk',
        string $shortName = 'г. Минск',
        ?RegionDistrict $regionDistrict = null,
    ): City {
        $city = new City();
        $this->setPrivate($city, 'name', $name);
        $this->setPrivate($city, 'slug', $slug);
        $this->setPrivate($city, 'shortName', $shortName);
        $this->setPrivate($city, 'ruralCouncil', null);
        $this->setPrivate($city, 'latitude', '53.9000000');
        $this->setPrivate($city, 'longitude', '27.5667000');
        $this->setPrivate($city, 'externalId', null);
        $this->setPrivate($city, 'isMain', true);
        $this->setPrivate($city, 'regionDistrict', $regionDistrict);

        $this->entityManager()->persist($city);
        $this->entityManager()->flush();

        return $city;
    }

    protected function createRegion(string $slug = 'minsk-region', string $name = 'Минская область'): Region
    {
        $region = new Region();
        $this->setPrivate($region, 'name', $name);
        $this->setPrivate($region, 'slug', $slug);
        $this->setPrivate($region, 'externalId', null);
        $this->setPrivate($region, 'code', null);

        $this->entityManager()->persist($region);
        $this->entityManager()->flush();

        return $region;
    }

    protected function createRegionDistrict(Region $region, string $slug = 'minsk-district', string $name = 'Минский район'): RegionDistrict
    {
        $district = new RegionDistrict();
        $this->setPrivate($district, 'region', $region);
        $this->setPrivate($district, 'name', $name);
        $this->setPrivate($district, 'slug', $slug);
        $this->setPrivate($district, 'externalId', null);
        $this->setPrivate($district, 'code', null);

        $this->entityManager()->persist($district);
        $this->entityManager()->flush();

        return $district;
    }

    /**
     * @param array{
     *     type?: string,
     *     dealType?: string,
     *     rooms?: int|null,
     *     area?: float,
     *     priceAmount?: int,
     *     priceCurrency?: string,
     *     nearMetro?: bool,
     *     landArea?: float|null,
     * } $options
     */
    protected function createProperty(User $owner, City $city, string $status = 'published', array $options = []): Property
    {
        $type = $options['type'] ?? 'apartment';
        $dealType = $options['dealType'] ?? 'daily';
        $rooms = array_key_exists('rooms', $options) ? $options['rooms'] : 2;
        $area = $options['area'] ?? 65.0;
        $priceAmount = $options['priceAmount'] ?? 15000000;
        $priceCurrency = $options['priceCurrency'] ?? 'BYN';

        $property = new Property(
            ownerId: $owner->getId(),
            type: $type,
            dealType: $dealType,
            title: 'Functional test apartment title',
            description: 'Functional test apartment description that is long enough for request validation.',
            price: Price::fromAmount($priceAmount, $priceCurrency),
            area: $area,
            rooms: $rooms,
            floor: 4,
            totalFloors: 9,
            bathrooms: 1,
            yearBuilt: 2015,
            renovation: null,
            balcony: null,
            livingArea: null,
            kitchenArea: null,
            dealConditions: null,
            maxDailyGuests: null,
            dailySingleBeds: null,
            dailyDoubleBeds: null,
            checkInTime: null,
            checkOutTime: null,
            address: Address::create('12', null),
            cityId: $city->getId(),
            coordinates: Coordinates::create(53.9045, 27.5615),
            landArea: array_key_exists('landArea', $options) ? $options['landArea'] : null,
        );

        if ($status !== 'draft') {
            $property->setStatus($status);
        }

        if (!empty($options['nearMetro'])) {
            $property->setNearMetro(true);
        }

        $this->entityManager()->persist($property);
        $this->entityManager()->flush();

        /** @var ExchangeRateService $exchange */
        $exchange = static::getContainer()->get(ExchangeRateService::class);
        $property->setPriceByn($exchange->calculatePriceByn($priceAmount, $priceCurrency));
        $this->entityManager()->flush();

        return $property;
    }

    protected function createMetroStation(City $city, int $id, string $slug = 'test-station'): MetroStation
    {
        $station = new MetroStation(
            $id,
            $city->getId(),
            'Test station',
            $slug,
            1,
            1,
            53.9,
            27.56,
        );
        $this->entityManager()->persist($station);
        $this->entityManager()->flush();

        return $station;
    }

    protected function linkPropertyToMetro(Property $property, MetroStation $station, float $distanceKm = 0.5): void
    {
        $link = new PropertyMetroStation($property->getId()->getValue(), $station->getId(), $distanceKm);
        $this->entityManager()->persist($link);
        $this->entityManager()->flush();
    }

    protected function entityManager(): EntityManagerInterface
    {
        return static::getContainer()->get(EntityManagerInterface::class);
    }

    protected function loginAndGetToken(string $email, string $password): string
    {
        if (!$this->hasJwtKeys()) {
            self::markTestSkipped('JWT keys are missing in config/jwt for login-based tests.');
        }

        $this->client->request(
            'POST',
            '/api/auth/login',
            server: ['CONTENT_TYPE' => 'application/json'],
            content: json_encode([
                'email' => $email,
                'password' => $password,
            ], JSON_THROW_ON_ERROR)
        );

        $response = json_decode((string) $this->client->getResponse()->getContent(), true, 512, JSON_THROW_ON_ERROR);

        return $response['token'] ?? '';
    }

    private function hasJwtKeys(): bool
    {
        return is_file(self::getContainer()->getParameter('kernel.project_dir') . '/config/jwt/private.pem')
            && is_file(self::getContainer()->getParameter('kernel.project_dir') . '/config/jwt/public.pem');
    }

    private function resetDatabase(): void
    {
        $metadata = $this->entityManager()->getMetadataFactory()->getAllMetadata();
        $schemaTool = new SchemaTool($this->entityManager());

        if ($metadata !== []) {
            $schemaTool->dropSchema($metadata);
            $schemaTool->createSchema($metadata);
        }
    }

    private function setPrivate(object $object, string $property, mixed $value): void
    {
        $reflection = new \ReflectionProperty($object, $property);
        $reflection->setAccessible(true);
        $reflection->setValue($object, $value);
    }
}
