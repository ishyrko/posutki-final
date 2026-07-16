<?php

declare(strict_types=1);

namespace App\Domain\Payment\Entity;

use App\Domain\Payment\Enum\PaymentStatus;
use App\Domain\Shared\Exception\DomainException;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity]
#[ORM\Table(name: 'payments')]
#[ORM\Index(columns: ['purchase_id'], name: 'idx_payments_purchase')]
#[ORM\Index(columns: ['checkout_token'], name: 'idx_payments_checkout_token')]
#[ORM\UniqueConstraint(name: 'uniq_payments_tracking_id', columns: ['tracking_id'])]
class Payment
{
    public const PROVIDER_BEPAID = 'bepaid';

    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(type: 'integer')]
    private ?int $id = null;

    #[ORM\Column(type: 'integer', name: 'purchase_id')]
    private int $purchaseId;

    #[ORM\Column(type: 'string', length: 30)]
    private string $provider;

    #[ORM\Column(type: 'string', length: 64, name: 'tracking_id')]
    private string $trackingId;

    #[ORM\Column(type: 'string', length: 128, nullable: true, name: 'checkout_token')]
    private ?string $checkoutToken = null;

    #[ORM\Column(type: 'string', length: 64, nullable: true, name: 'transaction_uid')]
    private ?string $transactionUid = null;

    #[ORM\Column(type: 'integer', name: 'amount_byn')]
    private int $amountByn;

    #[ORM\Column(type: 'string', length: 20)]
    private string $status;

    #[ORM\Column(type: 'text', nullable: true, name: 'raw_payload')]
    private ?string $rawPayload = null;

    #[ORM\Column(type: 'datetime_immutable', name: 'created_at')]
    private \DateTimeImmutable $createdAt;

    #[ORM\Column(type: 'datetime_immutable', nullable: true, name: 'paid_at')]
    private ?\DateTimeImmutable $paidAt = null;

    #[ORM\Column(type: 'datetime_immutable', nullable: true, name: 'failed_at')]
    private ?\DateTimeImmutable $failedAt = null;

    #[ORM\Column(type: 'text', nullable: true)]
    private ?string $note = null;

    public function __construct(
        int $purchaseId,
        string $trackingId,
        int $amountByn,
        string $provider = self::PROVIDER_BEPAID,
        ?\DateTimeImmutable $now = null,
    ) {
        if ($amountByn < 0) {
            throw new DomainException('Сумма платежа не может быть отрицательной');
        }
        if (trim($trackingId) === '') {
            throw new DomainException('tracking_id обязателен');
        }

        $now ??= new \DateTimeImmutable();
        $this->purchaseId = $purchaseId;
        $this->provider = $provider;
        $this->trackingId = $trackingId;
        $this->amountByn = $amountByn;
        $this->status = PaymentStatus::Pending->value;
        $this->createdAt = $now;
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getPurchaseId(): int
    {
        return $this->purchaseId;
    }

    public function getProvider(): string
    {
        return $this->provider;
    }

    public function getTrackingId(): string
    {
        return $this->trackingId;
    }

    public function getCheckoutToken(): ?string
    {
        return $this->checkoutToken;
    }

    public function getTransactionUid(): ?string
    {
        return $this->transactionUid;
    }

    public function getAmountByn(): int
    {
        return $this->amountByn;
    }

    public function getStatus(): string
    {
        return $this->status;
    }

    public function getRawPayload(): ?string
    {
        return $this->rawPayload;
    }

    public function getCreatedAt(): \DateTimeImmutable
    {
        return $this->createdAt;
    }

    public function getPaidAt(): ?\DateTimeImmutable
    {
        return $this->paidAt;
    }

    public function getFailedAt(): ?\DateTimeImmutable
    {
        return $this->failedAt;
    }

    public function getNote(): ?string
    {
        return $this->note;
    }

    public function isPending(): bool
    {
        return $this->status === PaymentStatus::Pending->value;
    }

    public function isTerminal(): bool
    {
        $status = PaymentStatus::tryFrom($this->status);

        return $status?->isTerminal() ?? true;
    }

    public function setCheckoutToken(string $checkoutToken): void
    {
        $this->checkoutToken = $checkoutToken;
    }

    public function setTransactionUid(?string $transactionUid): void
    {
        $this->transactionUid = $transactionUid !== null && $transactionUid !== '' ? $transactionUid : null;
    }

    public function setRawPayload(?string $rawPayload): void
    {
        $this->rawPayload = $rawPayload;
    }

    public function setNote(?string $note): void
    {
        $value = $note !== null ? trim($note) : null;
        $this->note = $value === '' ? null : $value;
    }

    public function markSuccessful(?\DateTimeImmutable $paidAt = null, ?string $transactionUid = null): void
    {
        if ($this->isTerminal() && $this->status === PaymentStatus::Successful->value) {
            return;
        }
        if ($this->isTerminal()) {
            throw new DomainException('Нельзя пометить терминальный платёж как успешный');
        }

        $this->status = PaymentStatus::Successful->value;
        $this->paidAt = $paidAt ?? new \DateTimeImmutable();
        if ($transactionUid !== null) {
            $this->setTransactionUid($transactionUid);
        }
    }

    public function markFailed(?string $note = null, ?\DateTimeImmutable $failedAt = null): void
    {
        if ($this->isTerminal() && $this->status === PaymentStatus::Failed->value) {
            return;
        }
        if ($this->isTerminal()) {
            throw new DomainException('Нельзя пометить терминальный платёж как неуспешный');
        }

        $this->status = PaymentStatus::Failed->value;
        $this->failedAt = $failedAt ?? new \DateTimeImmutable();
        $this->setNote($note);
    }

    public function markExpired(?string $note = null): void
    {
        if ($this->isTerminal()) {
            return;
        }

        $this->status = PaymentStatus::Expired->value;
        $this->failedAt = new \DateTimeImmutable();
        $this->setNote($note);
    }

    public function markCancelled(?string $note = null): void
    {
        if ($this->isTerminal()) {
            return;
        }

        $this->status = PaymentStatus::Cancelled->value;
        $this->failedAt = new \DateTimeImmutable();
        $this->setNote($note);
    }
}
