<?php

declare(strict_types=1);

namespace App\Tests\Application\Property;

use App\Application\Command\Property\CreateProperty\CreatePropertyCommand;
use App\Application\Command\Property\CreateProperty\CreatePropertyHandler;
use App\Application\Service\PropertyOwnerPublicContactResolver;
use App\Domain\Exchange\Repository\ExchangeRateRepositoryInterface;
use App\Domain\Property\Event\PropertySubmittedForModerationEvent;
use App\Domain\Property\Repository\PropertyRepositoryInterface;
use App\Domain\Property\Repository\MetroStationRepositoryInterface;
use App\Domain\Property\Repository\PropertyMetroStationRepositoryInterface;
use App\Domain\Shared\Exception\DomainException;
use App\Domain\Shared\ValueObject\Id;
use App\Domain\User\Service\DailyListingSellerProfileGuardInterface;
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
        $resolver = $this->createStub(PropertyOwnerPublicContactResolver::class);
        $resolver->method('assertOwnerHasPublicContact')->willReturnCallback(static function (): void {
        });
        $metroCalculator = $this->createMetroCalculator();
        $notificationBus = $this->createMock(MessageBusInterface::class);
        $exchangeRateService = $this->createExchangeRateService(['USD' => 3.2]);
        $guard = $this->createStub(DailyListingSellerProfileGuardInterface::class);

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
            $resolver,
            $exchangeRateService,
            $metroCalculator,
            $notificationBus,
            $guard,
        );

        $propertyId = $handler($this->createValidCommand(priceCurrency: 'USD'));

        self::assertSame(123, $propertyId);
    }

    public function testIncompleteProfileContactThrowsDomainException(): void
    {
        $propertyRepository = $this->createStub(PropertyRepositoryInterface::class);
        $resolver = $this->createStub(PropertyOwnerPublicContactResolver::class);
        $resolver
            ->method('assertOwnerHasPublicContact')
            ->willThrowException(new DomainException('Подтвердите телефон в профиле'));
        $metroCalculator = $this->createMetroCalculator();
        $notificationBus = $this->createStub(MessageBusInterface::class);
        $exchangeRateService = $this->createExchangeRateService();
        $guard = $this->createStub(DailyListingSellerProfileGuardInterface::class);

        $handler = new CreatePropertyHandler(
            $propertyRepository,
            $resolver,
            $exchangeRateService,
            $metroCalculator,
            $notificationBus,
            $guard,
        );

        $this->expectException(DomainException::class);
        $this->expectExceptionMessage('Подтвердите телефон в профиле');

        $handler($this->createValidCommand());
    }

    public function testDailyRentWithoutRequiredFieldsThrowsDomainException(): void
    {
        $propertyRepository = $this->createStub(PropertyRepositoryInterface::class);
        $resolver = $this->createStub(PropertyOwnerPublicContactResolver::class);
        $resolver->method('assertOwnerHasPublicContact')->willReturnCallback(static function (): void {
        });
        $metroCalculator = $this->createMetroCalculator();
        $notificationBus = $this->createStub(MessageBusInterface::class);
        $exchangeRateService = $this->createExchangeRateService();
        $guard = $this->createStub(DailyListingSellerProfileGuardInterface::class);

        $handler = new CreatePropertyHandler(
            $propertyRepository,
            $resolver,
            $exchangeRateService,
            $metroCalculator,
            $notificationBus,
            $guard,
        );

        $this->expectException(DomainException::class);
        $this->expectExceptionMessage('Укажите максимальное число гостей для посуточной аренды');

        $handler($this->createValidCommand(dealType: 'daily', maxDailyGuests: null, dailySingleBeds: null, dailyDoubleBeds: null));
    }

    private function createValidCommand(
        string $dealType = 'sale',
        string $priceCurrency = 'BYN',
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
