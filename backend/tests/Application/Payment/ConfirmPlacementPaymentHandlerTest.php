<?php

declare(strict_types=1);

namespace App\Tests\Application\Payment;

use App\Application\Command\Payment\ConfirmPlacementPayment\ConfirmPlacementPaymentCommand;
use App\Application\Command\Payment\ConfirmPlacementPayment\ConfirmPlacementPaymentHandler;
use App\Application\Service\FreeListingLimitService;
use App\Application\Service\PlacementPaymentCompletionService;
use App\Application\Service\PropertyPlacementService;
use App\Domain\Payment\Entity\Payment;
use App\Domain\Payment\Enum\PaymentStatus;
use App\Domain\Payment\Repository\PaymentRepositoryInterface;
use App\Domain\Property\Entity\Property;
use App\Domain\Property\Entity\PropertyPlacementPurchase;
use App\Domain\Property\Enum\PlacementPurchaseKind;
use App\Domain\Property\Enum\PlacementPurchaseStatus;
use App\Domain\Property\Repository\CityRepositoryInterface;
use App\Domain\Property\Repository\PropertyPlacementLevelPriceRepositoryInterface;
use App\Domain\Property\Repository\PropertyPlacementPurchaseRepositoryInterface;
use App\Domain\Property\Repository\PropertyPlacementScopeSettingsRepositoryInterface;
use App\Domain\Property\Repository\PropertyRepositoryInterface;
use App\Domain\User\Repository\UserRepositoryInterface;
use App\Domain\Property\ValueObject\Address;
use App\Domain\Property\ValueObject\Coordinates;
use App\Domain\Property\ValueObject\Price;
use App\Domain\Shared\ValueObject\Id;
use App\Infrastructure\Payment\BePaid\BePaidCheckoutReader;
use App\Infrastructure\Payment\BePaid\BePaidClientRegistry;
use App\Infrastructure\Payment\BePaid\BePaidGatewayClient;
use PHPUnit\Framework\TestCase;
use Psr\Log\LoggerInterface;
use Symfony\Component\Messenger\Envelope;
use Symfony\Component\Messenger\MessageBusInterface;
use Symfony\Contracts\HttpClient\HttpClientInterface;
use Symfony\Contracts\HttpClient\ResponseInterface;

final class ConfirmPlacementPaymentHandlerTest extends TestCase
{
    public function testSuccessfulReturnUrlConfirmsPaymentAndActivatesPurchase(): void
    {
        $ownerId = Id::fromInt(7);
        $purchase = $this->createPendingPurchase($ownerId);
        $property = $this->createProperty($ownerId);
        $payment = new Payment(42, 'plc-42-test', 49);
        $payment->setCheckoutToken('checkout-token');
        $this->setPaymentId($payment, 1);

        $purchaseRepository = $this->createMock(PropertyPlacementPurchaseRepositoryInterface::class);
        $purchaseRepository->expects(self::atLeastOnce())->method('findById')->with(42)->willReturn($purchase);

        $propertyRepository = $this->createMock(PropertyRepositoryInterface::class);
        $propertyRepository->method('findById')->willReturn($property);

        $paymentRepository = $this->createMock(PaymentRepositoryInterface::class);
        $paymentRepository->expects(self::once())->method('findByCheckoutToken')->with('checkout-token')->willReturn($payment);
        $paymentRepository->expects(self::atLeastOnce())->method('save')->with($payment);

        $bePaidClient = $this->createBePaidClient([
            'checkout' => [
                'gateway_response' => [
                    'payment' => [
                        'status' => 'successful',
                        'uid' => 'txn-uid-1',
                    ],
                ],
                'order' => [
                    'tracking_id' => 'plc-42-test',
                    'amount' => 4900,
                    'currency' => 'BYN',
                ],
            ],
        ]);

        $placementService = new PropertyPlacementService(
            $propertyRepository,
            $purchaseRepository,
            $this->createStub(PropertyPlacementLevelPriceRepositoryInterface::class),
            $this->createStub(PropertyPlacementScopeSettingsRepositoryInterface::class),
            $this->createStub(CityRepositoryInterface::class),
            $this->createStub(UserRepositoryInterface::class),
            $this->createStub(\App\Domain\Property\Service\ApartmentPlacementScopeResolver::class),
        );
        $purchaseRepository->method('findActiveLevelByPropertyId')->willReturnCallback(
            static fn (int $propertyId, ?\DateTimeImmutable $now = null): ?PropertyPlacementPurchase => $purchase->isActive() ? $purchase : null,
        );
        $purchaseRepository->method('save');
        $propertyRepository->expects(self::atLeastOnce())->method('save')->with($property);

        $handler = new ConfirmPlacementPaymentHandler(
            $purchaseRepository,
            $propertyRepository,
            $this->createStub(PropertyPlacementLevelPriceRepositoryInterface::class),
            $paymentRepository,
            $this->createRegistry($bePaidClient),
            new BePaidCheckoutReader(),
            new PlacementPaymentCompletionService(
                $paymentRepository,
                $purchaseRepository,
                $propertyRepository,
                $placementService,
                $this->createFreeListingLimitService(),
                $this->createNotificationBus(),
                $this->createStub(LoggerInterface::class),
            ),
        );

        $result = $handler(new ConfirmPlacementPaymentCommand(
            purchaseId: 42,
            userId: (string) $ownerId->getValue(),
            checkoutToken: 'checkout-token',
        ));

        self::assertSame(42, $result->id);
        self::assertSame(PaymentStatus::Successful->value, $payment->getStatus());
        self::assertSame(PlacementPurchaseStatus::Active->value, $purchase->getStatus());
    }

    public function testEposReturnUrlConfirmsPaymentThroughEposShop(): void
    {
        $ownerId = Id::fromInt(7);
        $purchase = $this->createPendingPurchase($ownerId);
        $property = $this->createProperty($ownerId);
        $payment = new Payment(42, 'plc-42-epos', 49, Payment::PROVIDER_BEPAID_EPOS);
        $payment->setCheckoutToken('epos-checkout-token');
        $this->setPaymentId($payment, 2);

        $purchaseRepository = $this->createMock(PropertyPlacementPurchaseRepositoryInterface::class);
        $purchaseRepository->expects(self::atLeastOnce())->method('findById')->with(42)->willReturn($purchase);

        $propertyRepository = $this->createMock(PropertyRepositoryInterface::class);
        $propertyRepository->method('findById')->willReturn($property);

        $paymentRepository = $this->createMock(PaymentRepositoryInterface::class);
        $paymentRepository->expects(self::once())->method('findByCheckoutToken')->with('epos-checkout-token')->willReturn($payment);
        $paymentRepository->expects(self::atLeastOnce())->method('save')->with($payment);

        $eposClient = $this->createBePaidClient([
            'checkout' => [
                'gateway_response' => [
                    'payment' => [
                        'status' => 'successful',
                        'uid' => 'epos-txn-uid',
                    ],
                ],
                'order' => [
                    'tracking_id' => 'plc-42-epos',
                    'amount' => 4900,
                    'currency' => 'BYN',
                ],
            ],
        ], 'epos-shop', 'epos-secret');

        $placementService = new PropertyPlacementService(
            $propertyRepository,
            $purchaseRepository,
            $this->createStub(PropertyPlacementLevelPriceRepositoryInterface::class),
            $this->createStub(PropertyPlacementScopeSettingsRepositoryInterface::class),
            $this->createStub(CityRepositoryInterface::class),
            $this->createStub(UserRepositoryInterface::class),
            $this->createStub(\App\Domain\Property\Service\ApartmentPlacementScopeResolver::class),
        );
        $purchaseRepository->method('findActiveLevelByPropertyId')->willReturnCallback(
            static fn (int $propertyId, ?\DateTimeImmutable $now = null): ?PropertyPlacementPurchase => $purchase->isActive() ? $purchase : null,
        );
        $purchaseRepository->method('save');
        $propertyRepository->expects(self::atLeastOnce())->method('save')->with($property);

        $idle = $this->createMock(HttpClientInterface::class);
        $idle->expects(self::never())->method('request');
        $cardClient = new BePaidGatewayClient(
            $idle,
            $this->createStub(LoggerInterface::class),
            '363',
            'secret',
            'https://checkout.bepaid.by',
            true,
        );

        $handler = new ConfirmPlacementPaymentHandler(
            $purchaseRepository,
            $propertyRepository,
            $this->createStub(PropertyPlacementLevelPriceRepositoryInterface::class),
            $paymentRepository,
            $this->createRegistry($cardClient, $eposClient),
            new BePaidCheckoutReader(),
            new PlacementPaymentCompletionService(
                $paymentRepository,
                $purchaseRepository,
                $propertyRepository,
                $placementService,
                $this->createFreeListingLimitService(),
                $this->createNotificationBus(),
                $this->createStub(LoggerInterface::class),
            ),
        );

        $result = $handler(new ConfirmPlacementPaymentCommand(
            purchaseId: 42,
            userId: (string) $ownerId->getValue(),
            checkoutToken: 'epos-checkout-token',
        ));

        self::assertSame(PlacementPurchaseStatus::Active->value, $result->status);
        self::assertSame(PaymentStatus::Successful->value, $payment->getStatus());
        self::assertSame('epos-txn-uid', $payment->getTransactionUid());
    }

    private function createPendingPurchase(Id $ownerId): PropertyPlacementPurchase
    {
        $purchase = new PropertyPlacementPurchase(
            propertyId: 10,
            ownerId: $ownerId,
            kind: PlacementPurchaseKind::Level->value,
            priceByn: 49,
            source: 'self_service',
            level: 1,
            durationMonths: 1,
        );

        $idReflection = new \ReflectionProperty($purchase, 'id');
        $idReflection->setAccessible(true);
        $idReflection->setValue($purchase, 42);

        return $purchase;
    }

    private function createProperty(Id $ownerId): Property
    {
        $property = new Property(
            ownerId: $ownerId,
            type: 'apartment',
            dealType: 'daily',
            title: 'Test property title for payment',
            description: 'Test property description long enough for validation rules.',
            price: Price::fromAmount(100, 'BYN'),
            area: 50.0,
            rooms: 2,
            floor: 3,
            totalFloors: 9,
            bathrooms: 1,
            yearBuilt: 2010,
            renovation: null,
            balcony: null,
            livingArea: null,
            kitchenArea: null,
            dealConditions: null,
            paymentMethods: null,
            maxDailyGuests: 4,
            dailySingleBeds: null,
            dailyDoubleBeds: null,
            checkInTime: null,
            checkOutTime: null,
            address: Address::create('1', null),
            cityId: 1,
            coordinates: Coordinates::create(53.9, 27.5),
            landArea: null,
        );
        $property->setStatus('published');

        $idReflection = new \ReflectionProperty($property, 'id');
        $idReflection->setAccessible(true);
        $idReflection->setValue($property, Id::fromInt(10));

        return $property;
    }

    private function setPaymentId(Payment $payment, int $id): void
    {
        $idReflection = new \ReflectionProperty($payment, 'id');
        $idReflection->setAccessible(true);
        $idReflection->setValue($payment, $id);
    }

    private function createRegistry(
        BePaidGatewayClient $cardClient,
        ?BePaidGatewayClient $eposClient = null,
        string $eposShopId = 'epos-shop',
        string $eposSecretKey = 'epos-secret',
    ): BePaidClientRegistry {
        $idle = $this->createMock(HttpClientInterface::class);
        $idle->expects(self::never())->method('request');

        return new BePaidClientRegistry(
            $cardClient,
            $eposClient ?? new BePaidGatewayClient(
                $idle,
                $this->createStub(LoggerInterface::class),
                'epos-shop',
                'epos-secret',
                'https://checkout.bepaid.by',
                true,
            ),
            $eposShopId,
            $eposSecretKey,
        );
    }

    /**
     * @param array<string, mixed> $checkoutBody
     */
    private function createBePaidClient(
        array $checkoutBody,
        string $shopId = '363',
        string $secretKey = 'secret',
    ): BePaidGatewayClient {
        $response = $this->createStub(ResponseInterface::class);
        $response->method('getStatusCode')->willReturn(200);
        $response->method('getContent')->willReturn(json_encode($checkoutBody, JSON_THROW_ON_ERROR));

        $httpClient = $this->createMock(HttpClientInterface::class);
        $httpClient
            ->expects(self::once())
            ->method('request')
            ->willReturn($response);

        return new BePaidGatewayClient(
            $httpClient,
            $this->createStub(LoggerInterface::class),
            $shopId,
            $secretKey,
            'https://checkout.bepaid.by',
            true,
        );
    }

    private function createFreeListingLimitService(): FreeListingLimitService
    {
        return new FreeListingLimitService(
            $this->createStub(PropertyRepositoryInterface::class),
            $this->createStub(CityRepositoryInterface::class),
            $this->createStub(UserRepositoryInterface::class),
        );
    }

    private function createNotificationBus(): MessageBusInterface
    {
        return new class implements MessageBusInterface {
            public function dispatch(object $message, array $stamps = []): Envelope
            {
                return new Envelope($message);
            }
        };
    }
}
