<?php

declare(strict_types=1);

namespace App\Tests\Application\Payment;

use App\Application\Command\Payment\CreatePlacementPayment\CreatePlacementPaymentCommand;
use App\Application\Command\Payment\CreatePlacementPayment\CreatePlacementPaymentHandler;
use App\Domain\Payment\Entity\Payment;
use App\Domain\Payment\Repository\PaymentRepositoryInterface;
use App\Domain\Property\Entity\PropertyPlacementPurchase;
use App\Domain\Property\Enum\PlacementPurchaseType;
use App\Domain\Property\Repository\PropertyPlacementPurchaseRepositoryInterface;
use App\Domain\Property\Repository\PropertyPlacementSlotRepositoryInterface;
use App\Domain\Property\Repository\PropertyRepositoryInterface;
use App\Domain\Shared\Exception\DomainException;
use App\Domain\Shared\ValueObject\Id;
use App\Infrastructure\Payment\BePaid\BePaidGatewayClient;
use PHPUnit\Framework\TestCase;

final class CreatePlacementPaymentHandlerTest extends TestCase
{
    public function testCreatesCheckoutAndReturnsRedirectUrl(): void
    {
        $ownerId = Id::fromInt(7);
        $purchase = $this->createPurchase(ownerId: $ownerId);

        $purchaseRepository = $this->createMock(PropertyPlacementPurchaseRepositoryInterface::class);
        $purchaseRepository->method('findById')->with(42)->willReturn($purchase);

        $paymentRepository = $this->createMock(PaymentRepositoryInterface::class);
        $paymentRepository->method('findLatestForPurchase')->with(42)->willReturn(null);
        $paymentRepository
            ->expects(self::exactly(2))
            ->method('save')
            ->with(self::isInstanceOf(Payment::class))
            ->willReturnCallback(function (Payment $payment): void {
                if ($payment->getId() === null) {
                    $idReflection = new \ReflectionProperty($payment, 'id');
                    $idReflection->setAccessible(true);
                    $idReflection->setValue($payment, 99);
                }
            });

        $bePaidClient = $this->createMock(BePaidGatewayClient::class);
        $bePaidClient
            ->expects(self::once())
            ->method('createCheckout')
            ->with(
                self::callback(static function (array $order): bool {
                    return $order['currency'] === 'BYN'
                        && $order['amount'] === 4900
                        && str_starts_with((string) $order['tracking_id'], 'plc-42-');
                }),
                self::callback(static function (array $settings): bool {
                    return str_contains($settings['notification_url'], '/api/webhooks/bepaid')
                        && str_contains($settings['success_url'], '/kabinet/oplata/42/');
                }),
                self::anything(),
            )
            ->willReturn([
                'token' => 'checkout-token-abc',
                'redirectUrl' => 'https://checkout.bepaid.by/pay/abc',
            ]);

        $handler = new CreatePlacementPaymentHandler(
            $purchaseRepository,
            $this->createStub(PropertyRepositoryInterface::class),
            $this->createStub(PropertyPlacementSlotRepositoryInterface::class),
            $paymentRepository,
            $bePaidClient,
            'http://localhost:3000',
            'http://localhost',
        );

        $result = $handler(new CreatePlacementPaymentCommand(
            purchaseId: 42,
            userId: (string) $ownerId->getValue(),
            customerIp: '127.0.0.1',
            customerEmail: 'user@example.com',
        ));

        self::assertSame('https://checkout.bepaid.by/pay/abc', $result['redirectUrl']);
        self::assertSame(99, $result['paymentId']);
    }

    public function testThrowsWhenPurchaseNotOwnedByUser(): void
    {
        $purchase = $this->createPurchase(ownerId: Id::fromInt(1));

        $purchaseRepository = $this->createStub(PropertyPlacementPurchaseRepositoryInterface::class);
        $purchaseRepository->method('findById')->willReturn($purchase);

        $handler = new CreatePlacementPaymentHandler(
            $purchaseRepository,
            $this->createStub(PropertyRepositoryInterface::class),
            $this->createStub(PropertyPlacementSlotRepositoryInterface::class),
            $this->createStub(PaymentRepositoryInterface::class),
            $this->createStub(BePaidGatewayClient::class),
            'http://localhost:3000',
            'http://localhost',
        );

        $this->expectException(DomainException::class);
        $this->expectExceptionMessage('Нет прав');

        $handler(new CreatePlacementPaymentCommand(
            purchaseId: 42,
            userId: '999',
        ));
    }

    private function createPurchase(Id $ownerId): PropertyPlacementPurchase
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
}
