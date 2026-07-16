<?php

declare(strict_types=1);

namespace App\Tests\Application\Payment;

use App\Application\Command\Payment\ProcessBePaidWebhook\ProcessBePaidWebhookCommand;
use App\Application\Command\Payment\ProcessBePaidWebhook\ProcessBePaidWebhookHandler;
use App\Application\Service\PlacementPaymentCompletionService;
use App\Domain\Payment\Entity\Payment;
use App\Domain\Payment\Repository\PaymentRepositoryInterface;
use PHPUnit\Framework\TestCase;
use Psr\Log\LoggerInterface;

final class ProcessBePaidWebhookHandlerTest extends TestCase
{
    public function testSuccessfulWebhookActivatesPendingPurchase(): void
    {
        $payment = new Payment(42, 'plc-42-test', 49);
        $payment->setCheckoutToken('token-1');
        $this->setPaymentId($payment, 1);

        $paymentRepository = $this->createMock(PaymentRepositoryInterface::class);
        $paymentRepository->method('findByCheckoutToken')->with('token-1')->willReturn($payment);

        $completionService = $this->createMock(PlacementPaymentCompletionService::class);
        $completionService
            ->expects(self::once())
            ->method('completeSuccessfulPayment')
            ->with($payment, 'txn-uid-1');

        $handler = new ProcessBePaidWebhookHandler(
            $paymentRepository,
            $completionService,
            $this->createStub(LoggerInterface::class),
        );

        $payload = [
            'token' => 'token-1',
            'status' => 'successful',
            'order' => [
                'tracking_id' => 'plc-42-test',
                'amount' => 4900,
                'currency' => 'BYN',
            ],
            'uid' => 'txn-uid-1',
        ];

        $handler(new ProcessBePaidWebhookCommand($payload, json_encode($payload, JSON_THROW_ON_ERROR), true));
    }

    public function testTerminalPaymentIsIdempotent(): void
    {
        $payment = new Payment(42, 'plc-42-test', 49);
        $payment->setCheckoutToken('token-1');
        $payment->markSuccessful();
        $this->setPaymentId($payment, 1);

        $paymentRepository = $this->createMock(PaymentRepositoryInterface::class);
        $paymentRepository->method('findByCheckoutToken')->willReturn($payment);
        $paymentRepository->expects(self::once())->method('save')->with($payment);

        $completionService = $this->createMock(PlacementPaymentCompletionService::class);
        $completionService->expects(self::never())->method('completeSuccessfulPayment');

        $handler = new ProcessBePaidWebhookHandler(
            $paymentRepository,
            $completionService,
            $this->createStub(LoggerInterface::class),
        );

        $payload = [
            'token' => 'token-1',
            'status' => 'successful',
            'order' => [
                'tracking_id' => 'plc-42-test',
                'amount' => 4900,
                'currency' => 'BYN',
            ],
        ];

        $handler(new ProcessBePaidWebhookCommand($payload, json_encode($payload, JSON_THROW_ON_ERROR), true));
    }

    public function testInvalidSignatureIsIgnored(): void
    {
        $paymentRepository = $this->createMock(PaymentRepositoryInterface::class);
        $paymentRepository->expects(self::never())->method('save');

        $handler = new ProcessBePaidWebhookHandler(
            $paymentRepository,
            $this->createStub(PlacementPaymentCompletionService::class),
            $this->createStub(LoggerInterface::class),
        );

        $handler(new ProcessBePaidWebhookCommand(['token' => 'x'], '{}', false));
    }

    private function setPaymentId(Payment $payment, int $id): void
    {
        $idReflection = new \ReflectionProperty($payment, 'id');
        $idReflection->setAccessible(true);
        $idReflection->setValue($payment, $id);
    }
}
