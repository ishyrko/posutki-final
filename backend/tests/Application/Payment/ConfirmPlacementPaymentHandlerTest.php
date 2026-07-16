<?php

declare(strict_types=1);

namespace App\Tests\Application\Payment;

use App\Application\Command\Payment\ConfirmPlacementPayment\ConfirmPlacementPaymentCommand;
use App\Application\Command\Payment\ConfirmPlacementPayment\ConfirmPlacementPaymentHandler;
use App\Application\Service\PlacementPaymentCompletionService;
use App\Application\Service\PropertyPlacementService;
use App\Domain\Payment\Entity\Payment;
use App\Domain\Payment\Enum\PaymentStatus;
use App\Domain\Payment\Repository\PaymentRepositoryInterface;
use App\Domain\Property\Entity\Property;
use App\Domain\Property\Entity\PropertyPlacementPurchase;
use App\Domain\Property\Enum\PlacementPurchaseStatus;
use App\Domain\Property\Enum\PlacementPurchaseType;
use App\Domain\Property\Repository\PropertyPlacementPurchaseRepositoryInterface;
use App\Domain\Property\Repository\PropertyPlacementSlotRepositoryInterface;
use App\Domain\Property\Repository\PropertyRepositoryInterface;
use App\Domain\Property\ValueObject\Address;
use App\Domain\Property\ValueObject\Coordinates;
use App\Domain\Property\ValueObject\Price;
use App\Domain\Shared\ValueObject\Id;
use App\Infrastructure\Payment\BePaid\BePaidCheckoutReader;
use App\Infrastructure\Payment\BePaid\BePaidGatewayClient;
use PHPUnit\Framework\TestCase;
use Psr\Log\LoggerInterface;
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
        $purchaseRepository->method('findById')->with(42)->willReturnOnConsecutiveCalls($purchase, $purchase);

        $propertyRepository = $this->createStub(PropertyRepositoryInterface::class);
        $propertyRepository->method('findById')->willReturn($property);

        $paymentRepository = $this->createMock(PaymentRepositoryInterface::class);
        $paymentRepository->method('findByCheckoutToken')->with('checkout-token')->willReturn($payment);
        $paymentRepository->expects(self::once())->method('save')->with($payment);

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

        $placementService = $this->createMock(PropertyPlacementService::class);
        $placementService
            ->expects(self::once())
            ->method('activatePurchase')
            ->with($purchase, $property, null);

        $handler = new ConfirmPlacementPaymentHandler(
            $purchaseRepository,
            $propertyRepository,
            $this->createStub(PropertyPlacementSlotRepositoryInterface::class),
            $paymentRepository,
            $bePaidClient,
            new BePaidCheckoutReader(),
            new PlacementPaymentCompletionService(
                $paymentRepository,
                $purchaseRepository,
                $propertyRepository,
                $placementService,
                $this->createStub(MessageBusInterface::class),
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

    private function createPendingPurchase(Id $ownerId): PropertyPlacementPurchase
    {
        $purchase = new PropertyPlacementPurchase(
            propertyId: 10,
            ownerId: $ownerId,
            type: PlacementPurchaseType::Standard->value,
            durationMonths: 1,
            priceByn: 49,
            source: 'self_service',
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

    /**
     * @param array<string, mixed> $checkoutBody
     */
    private function createBePaidClient(array $checkoutBody): BePaidGatewayClient
    {
        $response = $this->createMock(ResponseInterface::class);
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
            '363',
            'secret',
            'https://checkout.bepaid.by',
            true,
        );
    }
}
