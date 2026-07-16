<?php

declare(strict_types=1);

namespace App\Tests\Application\Payment;

use App\Application\Command\Payment\CreatePlacementPayment\CreatePlacementPaymentCommand;
use App\Application\Command\Payment\CreatePlacementPayment\CreatePlacementPaymentHandler;
use App\Domain\Payment\Entity\Payment;
use App\Domain\Payment\Repository\PaymentRepositoryInterface;
use App\Domain\Property\Entity\PropertyPlacementPurchase;
use App\Domain\Property\Enum\PlacementPurchaseKind;
use App\Domain\Property\Repository\PropertyPlacementPurchaseRepositoryInterface;
use App\Domain\Property\Repository\PropertyRepositoryInterface;
use App\Domain\Shared\Exception\DomainException;
use App\Domain\Shared\ValueObject\Id;
use App\Infrastructure\Payment\BePaid\BePaidGatewayClient;
use PHPUnit\Framework\TestCase;
use Psr\Log\LoggerInterface;
use Symfony\Contracts\HttpClient\HttpClientInterface;
use Symfony\Contracts\HttpClient\ResponseInterface;

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

        $bePaidClient = $this->createBePaidClientForCheckout([
            'checkout' => [
                'token' => 'checkout-token-abc',
                'redirect_url' => 'https://checkout.bepaid.by/pay/abc',
            ],
        ]);

        $handler = new CreatePlacementPaymentHandler(
            $purchaseRepository,
            $this->createStub(PropertyRepositoryInterface::class),
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

        $httpClient = $this->createMock(HttpClientInterface::class);
        $httpClient->expects(self::never())->method('request');

        $handler = new CreatePlacementPaymentHandler(
            $purchaseRepository,
            $this->createStub(PropertyRepositoryInterface::class),
            $this->createStub(PaymentRepositoryInterface::class),
            new BePaidGatewayClient(
                $httpClient,
                $this->createStub(LoggerInterface::class),
                '363',
                'secret',
                'https://checkout.bepaid.by',
                true,
            ),
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

    /**
     * @param array<string, mixed> $checkoutBody
     */
    private function createBePaidClientForCheckout(array $checkoutBody): BePaidGatewayClient
    {
        $response = $this->createMock(ResponseInterface::class);
        $response->method('getStatusCode')->willReturn(200);
        $response->method('getContent')->willReturnCallback(
            static fn (?bool $throw = true): string => json_encode($checkoutBody, JSON_THROW_ON_ERROR),
        );

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
