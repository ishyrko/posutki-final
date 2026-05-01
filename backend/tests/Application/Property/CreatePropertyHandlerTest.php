<?php

declare(strict_types=1);

namespace App\Tests\Application\Property;

use App\Application\Command\Property\CreateProperty\CreatePropertyCommand;
use App\Application\Command\Property\CreateProperty\CreatePropertyHandler;
use App\Domain\Exchange\Repository\ExchangeRateRepositoryInterface;
use App\Domain\Property\Event\PropertySubmittedForModerationEvent;
use App\Domain\Property\Repository\PropertyRepositoryInterface;
use App\Domain\Property\Repository\MetroStationRepositoryInterface;
use App\Domain\Property\Repository\PropertyMetroStationRepositoryInterface;
use App\Domain\Shared\Exception\DomainException;
use App\Domain\Shared\ValueObject\Id;
use App\Domain\User\Entity\UserPhone;
use App\Domain\User\Repository\UserPhoneRepositoryInterface;
use App\Infrastructure\Service\ExchangeRateService;
use App\Infrastructure\Service\MetroProximityCalculator;
use Doctrine\ORM\EntityManagerInterface;
use PHPUnit\Framework\TestCase;
use Psr\Log\LoggerInterface;
use Symfony\Component\Messenger\Envelope;
use Symfony\Component\Messenger\MessageBusInterface;
use Symfony\Contracts\HttpClient\HttpClientInterface;

final class CreatePropertyHandlerTest extends TestCase
{
    public function testSuccessfulCreateSavesPropertyAndDispatchesModerationEvent(): void
    {
        $propertyRepository = $this->createMock(PropertyRepositoryInterface::class);
        $userPhoneRepository = $this->createStub(UserPhoneRepositoryInterface::class);
        $metroCalculator = $this->createMetroCalculator();
        $notificationBus = $this->createMock(MessageBusInterface::class);
        $exchangeRateService = $this->createExchangeRateService(['USD' => 3.2]);

        $propertyRepository
            ->expects(self::exactly(2))
            ->method('save')
            ->with(self::isInstanceOf(\App\Domain\Property\Entity\Property::class))
            ->willReturnCallback(function ($property): void {
                $idReflection = new \ReflectionProperty($property, 'id');
                $idReflection->setAccessible(true);
                if (!$idReflection->isInitialized($property)) {
                    $idReflection->setValue($property, Id::fromInt(123));
                }
            });

        $notificationBus
            ->expects(self::once())
            ->method('dispatch')
            ->with(self::callback(static function (object $event): bool {
                return $event instanceof PropertySubmittedForModerationEvent
                    && $event->propertyId === '123';
            }))
            ->willReturn(new Envelope(new \stdClass()));

        $handler = new CreatePropertyHandler(
            $propertyRepository,
            $userPhoneRepository,
            $exchangeRateService,
            $metroCalculator,
            $notificationBus
        );

        $propertyId = $handler($this->createValidCommand(priceCurrency: 'USD'));

        self::assertSame(123, $propertyId);
    }

    public function testUnverifiedContactPhoneThrowsDomainException(): void
    {
        $propertyRepository = $this->createStub(PropertyRepositoryInterface::class);
        $userPhoneRepository = $this->createStub(UserPhoneRepositoryInterface::class);
        $metroCalculator = $this->createMetroCalculator();
        $notificationBus = $this->createStub(MessageBusInterface::class);
        $exchangeRateService = $this->createExchangeRateService();

        $userPhoneRepository
            ->method('findByUserIdAndPhone')
            ->willReturn(null);

        $handler = new CreatePropertyHandler(
            $propertyRepository,
            $userPhoneRepository,
            $exchangeRateService,
            $metroCalculator,
            $notificationBus
        );

        $this->expectException(DomainException::class);
        $this->expectExceptionMessage('Подтвердите контактный телефон');

        $handler($this->createValidCommand(contactPhone: '+375291112233'));
    }

    public function testDailyRentWithoutRequiredFieldsThrowsDomainException(): void
    {
        $propertyRepository = $this->createStub(PropertyRepositoryInterface::class);
        $userPhoneRepository = $this->createStub(UserPhoneRepositoryInterface::class);
        $metroCalculator = $this->createMetroCalculator();
        $notificationBus = $this->createStub(MessageBusInterface::class);
        $exchangeRateService = $this->createExchangeRateService();

        $handler = new CreatePropertyHandler(
            $propertyRepository,
            $userPhoneRepository,
            $exchangeRateService,
            $metroCalculator,
            $notificationBus
        );

        $this->expectException(DomainException::class);
        $this->expectExceptionMessage('Укажите максимальное число гостей для посуточной аренды');

        $handler($this->createValidCommand(dealType: 'daily', maxDailyGuests: null, dailySingleBeds: null, dailyDoubleBeds: null));
    }

    public function testVerifiedContactPhoneAllowsCreate(): void
    {
        $propertyRepository = $this->createMock(PropertyRepositoryInterface::class);
        $userPhoneRepository = $this->createStub(UserPhoneRepositoryInterface::class);
        $metroCalculator = $this->createMetroCalculator();
        $notificationBus = $this->createMock(MessageBusInterface::class);
        $exchangeRateService = $this->createExchangeRateService();

        $verifiedPhone = new UserPhone(Id::fromInt(1), '+375291112233');
        $verifiedPhone->setVerificationCode('123456', new \DateTimeImmutable('+1 hour'));
        $verifiedPhone->verify('123456');

        $userPhoneRepository
            ->method('findByUserIdAndPhone')
            ->willReturn($verifiedPhone);

        $propertyRepository
            ->expects(self::exactly(2))
            ->method('save')
            ->willReturnCallback(function ($property): void {
                $idReflection = new \ReflectionProperty($property, 'id');
                $idReflection->setAccessible(true);
                if (!$idReflection->isInitialized($property)) {
                    $idReflection->setValue($property, Id::fromInt(124));
                }
            });

        $notificationBus
            ->expects(self::once())
            ->method('dispatch')
            ->willReturn(new Envelope(new \stdClass()));

        $handler = new CreatePropertyHandler(
            $propertyRepository,
            $userPhoneRepository,
            $exchangeRateService,
            $metroCalculator,
            $notificationBus
        );

        $id = $handler($this->createValidCommand(contactPhone: '+375291112233'));

        self::assertSame(124, $id);
    }

    private function createValidCommand(
        string $dealType = 'sale',
        string $priceCurrency = 'BYN',
        ?string $contactPhone = null,
        ?int $maxDailyGuests = null,
        ?int $dailySingleBeds = null,
        ?int $dailyDoubleBeds = null
    ): CreatePropertyCommand {
        return new CreatePropertyCommand(
            ownerId: '1',
            type: 'apartment',
            dealType: $dealType,
            title: 'Spacious apartment in the center',
            description: 'A long enough listing description that satisfies domain validation rules.',
            priceAmount: 25000000,
            priceCurrency: $priceCurrency,
            area: 75.5,
            rooms: 3,
            floor: 5,
            totalFloors: 12,
            building: '10A',
            cityId: 1,
            latitude: 53.9045,
            longitude: 27.5615,
            bathrooms: 1,
            yearBuilt: 2018,
            maxDailyGuests: $maxDailyGuests,
            dailySingleBeds: $dailySingleBeds,
            dailyDoubleBeds: $dailyDoubleBeds,
            checkInTime: '14:00',
            checkOutTime: '12:00',
            contactPhone: $contactPhone,
            contactName: 'Ivan',
        );
    }

    private function createExchangeRateService(array $rates = []): ExchangeRateService
    {
        $rateRepository = $this->createStub(ExchangeRateRepositoryInterface::class);
        $rateRepository->method('getAllRates')->willReturn($rates);

        return new ExchangeRateService(
            $rateRepository,
            $this->createStub(EntityManagerInterface::class),
            $this->createStub(HttpClientInterface::class),
            $this->createStub(LoggerInterface::class)
        );
    }

    private function createMetroCalculator(): MetroProximityCalculator
    {
        $metroStationRepository = $this->createStub(MetroStationRepositoryInterface::class);
        $metroStationRepository->method('findAll')->willReturn([]);

        $propertyMetroStationRepository = $this->createStub(PropertyMetroStationRepositoryInterface::class);

        return new MetroProximityCalculator($metroStationRepository, $propertyMetroStationRepository);
    }
}
