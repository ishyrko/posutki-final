<?php

declare(strict_types=1);

namespace App\Tests\Application\Payment;

use App\Application\Command\Payment\CreatePlacementPayment\CreatePlacementPaymentCommand;
use App\Application\Command\Payment\CreatePlacementPayment\CreatePlacementPaymentHandler;
use App\Domain\Payment\Entity\Payment;
use App\Domain\Payment\Enum\PaymentMethod;
use App\Domain\Payment\Repository\PaymentRepositoryInterface;
use App\Domain\Property\Entity\PropertyPlacementPurchase;
use App\Domain\Property\Enum\PlacementPurchaseKind;
use App\Domain\Property\Repository\PropertyPlacementPurchaseRepositoryInterface;
use App\Domain\Property\Repository\PropertyRepositoryInterface;
use App\Domain\Shared\Exception\DomainException;
use App\Domain\Shared\ValueObject\Id;
use App\Infrastructure\Payment\BePaid\BePaidClientRegistry;
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
        $purchaseRepository->expects(self::once())->method('findById')->with(42)->willReturn($purchase);

        $paymentRepository = $this->createMock(PaymentRepositoryInterface::class);
        $paymentRepository->expects(self::once())->method('findLatestForPurchase')->with(42)->willReturn(null);
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

        $handler = $this->createHandler(
            $purchaseRepository,
            $paymentRepository,
            $this->createRegistry($bePaidClient),
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

        $handler = $this->createHandler(
            $purchaseRepository,
            $this->createStub(PaymentRepositoryInterface::class),
            $this->createRegistry(new BePaidGatewayClient(
                $httpClient,
                $this->createStub(LoggerInterface::class),
                '363',
                'secret',
                'https://checkout.bepaid.by',
                true,
            )),
        );

        $this->expectException(DomainException::class);
        $this->expectExceptionMessage('Нет прав');

        $handler(new CreatePlacementPaymentCommand(
            purchaseId: 42,
            userId: '999',
        ));
    }

    public function testEposCheckoutStoresProviderAndSendsExpiry(): void
    {
        $ownerId = Id::fromInt(7);
        $purchase = $this->createPurchase(ownerId: $ownerId);

        $purchaseRepository = $this->createMock(PropertyPlacementPurchaseRepositoryInterface::class);
        $purchaseRepository->expects(self::once())->method('findById')->with(42)->willReturn($purchase);

        $saved = null;
        $paymentRepository = $this->createMock(PaymentRepositoryInterface::class);
        $paymentRepository->expects(self::once())->method('findLatestForPurchase')->with(42)->willReturn(null);
        $paymentRepository
            ->expects(self::exactly(2))
            ->method('save')
            ->willReturnCallback(function (Payment $payment) use (&$saved): void {
                $saved = $payment;
                if ($payment->getId() === null) {
                    $idReflection = new \ReflectionProperty($payment, 'id');
                    $idReflection->setAccessible(true);
                    $idReflection->setValue($payment, 100);
                }
            });

        $captured = null;
        $eposClient = $this->createBePaidClientForCheckout(
            [
                'checkout' => [
                    'token' => 'epos-token',
                    'redirect_url' => 'https://checkout.bepaid.by/pay/epos',
                ],
            ],
            $captured,
            'epos-shop',
            'epos-secret',
        );

        $handler = $this->createHandler(
            $purchaseRepository,
            $paymentRepository,
            $this->createRegistry($this->createIdleClient(), $eposClient),
        );

        $result = $handler(new CreatePlacementPaymentCommand(
            purchaseId: 42,
            userId: (string) $ownerId->getValue(),
            method: PaymentMethod::Epos->value,
        ));

        self::assertSame('https://checkout.bepaid.by/pay/epos', $result['redirectUrl']);
        self::assertInstanceOf(Payment::class, $saved);
        self::assertSame(Payment::PROVIDER_BEPAID_EPOS, $saved->getProvider());
        self::assertSame(PaymentMethod::Epos, $saved->getMethod());

        self::assertIsArray($captured);
        $checkout = $captured['json']['checkout'] ?? null;
        self::assertIsArray($checkout);
        self::assertSame(['epos-shop', 'epos-secret'], $captured['auth_basic'] ?? null);
        self::assertSame(
            'http://localhost/api/webhooks/bepaid/epos',
            $checkout['settings']['notification_url'] ?? null,
        );
        $expiredAt = $checkout['order']['expired_at'] ?? null;
        self::assertIsString($expiredAt);
        self::assertSame(
            $purchase->getReservationExpiresAt()?->format(\DateTimeInterface::ATOM),
            $expiredAt,
        );
    }

    public function testDoesNotReuseCardCheckoutForEpos(): void
    {
        $ownerId = Id::fromInt(7);
        $purchase = $this->createPurchase(ownerId: $ownerId);

        $cardPayment = new Payment(42, 'plc-42-card', 49);
        $cardPayment->setCheckoutToken('card-token');

        $purchaseRepository = $this->createStub(PropertyPlacementPurchaseRepositoryInterface::class);
        $purchaseRepository->method('findById')->willReturn($purchase);

        $paymentRepository = $this->createMock(PaymentRepositoryInterface::class);
        $paymentRepository->method('findLatestForPurchase')->willReturn($cardPayment);
        $paymentRepository->expects(self::exactly(2))->method('save');

        $captured = null;
        $eposClient = $this->createBePaidClientForCheckout(
            [
                'checkout' => [
                    'token' => 'epos-token',
                    'redirect_url' => 'https://checkout.bepaid.by/pay/epos',
                ],
            ],
            $captured,
            'epos-shop',
            'epos-secret',
        );

        $handler = $this->createHandler(
            $purchaseRepository,
            $paymentRepository,
            $this->createRegistry($this->createIdleClient(), $eposClient),
        );

        $result = $handler(new CreatePlacementPaymentCommand(
            purchaseId: 42,
            userId: (string) $ownerId->getValue(),
            method: PaymentMethod::Epos->value,
        ));

        self::assertSame('https://checkout.bepaid.by/pay/epos', $result['redirectUrl']);
        self::assertSame(['epos-shop', 'epos-secret'], $captured['auth_basic'] ?? null);
    }

    public function testReusesPendingCheckoutOfTheSameMethod(): void
    {
        $ownerId = Id::fromInt(7);
        $purchase = $this->createPurchase(ownerId: $ownerId);

        $existing = new Payment(42, 'plc-42-epos', 49, Payment::PROVIDER_BEPAID_EPOS);
        $existing->setCheckoutToken('epos-token');
        $idReflection = new \ReflectionProperty($existing, 'id');
        $idReflection->setAccessible(true);
        $idReflection->setValue($existing, 77);

        $purchaseRepository = $this->createStub(PropertyPlacementPurchaseRepositoryInterface::class);
        $purchaseRepository->method('findById')->willReturn($purchase);

        $paymentRepository = $this->createMock(PaymentRepositoryInterface::class);
        $paymentRepository->method('findLatestForPurchase')->willReturn($existing);
        $paymentRepository->expects(self::never())->method('save');

        $statusResponse = $this->createStub(ResponseInterface::class);
        $statusResponse->method('getStatusCode')->willReturn(200);
        $statusResponse->method('getContent')->willReturn(json_encode([
            'checkout' => ['redirect_url' => 'https://checkout.bepaid.by/pay/existing'],
        ], JSON_THROW_ON_ERROR));

        $httpClient = $this->createMock(HttpClientInterface::class);
        $httpClient
            ->expects(self::once())
            ->method('request')
            ->with('GET', 'https://checkout.bepaid.by/ctp/api/checkouts/epos-token')
            ->willReturn($statusResponse);

        $eposClient = new BePaidGatewayClient(
            $httpClient,
            $this->createStub(LoggerInterface::class),
            'epos-shop',
            'epos-secret',
            'https://checkout.bepaid.by',
            true,
        );

        $handler = $this->createHandler(
            $purchaseRepository,
            $paymentRepository,
            $this->createRegistry($this->createIdleClient(), $eposClient),
        );

        $result = $handler(new CreatePlacementPaymentCommand(
            purchaseId: 42,
            userId: (string) $ownerId->getValue(),
            method: PaymentMethod::Epos->value,
        ));

        self::assertSame('https://checkout.bepaid.by/pay/existing', $result['redirectUrl']);
        self::assertSame(77, $result['paymentId']);
    }

    public function testEposIsUnavailableWhenCredentialsAreMissing(): void
    {
        $ownerId = Id::fromInt(7);
        $purchase = $this->createPurchase(ownerId: $ownerId);

        $purchaseRepository = $this->createStub(PropertyPlacementPurchaseRepositoryInterface::class);
        $purchaseRepository->method('findById')->willReturn($purchase);

        $handler = $this->createHandler(
            $purchaseRepository,
            $this->createStub(PaymentRepositoryInterface::class),
            new BePaidClientRegistry($this->createIdleClient(), $this->createIdleClient(), '', ''),
        );

        $this->expectException(DomainException::class);
        $this->expectExceptionMessage('Оплата через ЕРИП временно недоступна');

        $handler(new CreatePlacementPaymentCommand(
            purchaseId: 42,
            userId: (string) $ownerId->getValue(),
            method: PaymentMethod::Epos->value,
        ));
    }

    private function createHandler(
        PropertyPlacementPurchaseRepositoryInterface $purchaseRepository,
        PaymentRepositoryInterface $paymentRepository,
        BePaidClientRegistry $registry,
    ): CreatePlacementPaymentHandler {
        return new CreatePlacementPaymentHandler(
            $purchaseRepository,
            $this->createStub(PropertyRepositoryInterface::class),
            $paymentRepository,
            $registry,
            'http://localhost:3000',
            'http://localhost',
        );
    }

    private function createRegistry(
        BePaidGatewayClient $cardClient,
        ?BePaidGatewayClient $eposClient = null,
    ): BePaidClientRegistry {
        return new BePaidClientRegistry(
            $cardClient,
            $eposClient ?? $this->createIdleClient(),
            'epos-shop',
            'epos-secret',
        );
    }

    private function createIdleClient(): BePaidGatewayClient
    {
        $httpClient = $this->createMock(HttpClientInterface::class);
        $httpClient->expects(self::never())->method('request');

        return new BePaidGatewayClient(
            $httpClient,
            $this->createStub(LoggerInterface::class),
            '363',
            'secret',
            'https://checkout.bepaid.by',
            true,
        );
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
     * @param array<string, mixed>|null $capturedRequest
     */
    private function createBePaidClientForCheckout(
        array $checkoutBody,
        ?array &$capturedRequest = null,
        string $shopId = '363',
        string $secretKey = 'secret',
    ): BePaidGatewayClient {
        $response = $this->createStub(ResponseInterface::class);
        $response->method('getStatusCode')->willReturn(200);
        $response->method('getContent')->willReturnCallback(
            static fn (?bool $throw = true): string => json_encode($checkoutBody, JSON_THROW_ON_ERROR),
        );

        $httpClient = $this->createMock(HttpClientInterface::class);
        $httpClient
            ->expects(self::once())
            ->method('request')
            ->willReturnCallback(function (string $method, string $url, array $options = []) use ($response, &$capturedRequest): ResponseInterface {
                $capturedRequest = $options;

                return $response;
            });

        return new BePaidGatewayClient(
            $httpClient,
            $this->createStub(LoggerInterface::class),
            $shopId,
            $secretKey,
            'https://checkout.bepaid.by',
            true,
        );
    }
}
