<?php

declare(strict_types=1);

namespace App\Domain\Property\Entity;

use App\Domain\Property\Enum\PlacementPurchaseStatus;
use App\Domain\Property\Enum\PlacementPurchaseType;
use App\Domain\Shared\Exception\DomainException;
use App\Domain\Shared\ValueObject\Id;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity]
#[ORM\Table(name: 'property_placement_purchases')]
#[ORM\Index(columns: ['property_id', 'status'], name: 'idx_placement_purchases_property_status')]
#[ORM\Index(columns: ['slot_id', 'status'], name: 'idx_placement_purchases_slot_status')]
#[ORM\Index(columns: ['owner_id'], name: 'idx_placement_purchases_owner')]
class PropertyPlacementPurchase
{
    public const RESERVATION_HOURS = 48;

    /** @var list<int> */
    public const ALLOWED_DURATIONS = [1, 3, 6, 12];

    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(type: 'integer')]
    private ?int $id = null;

    #[ORM\Column(type: 'integer', name: 'property_id')]
    private int $propertyId;

    #[ORM\Column(type: 'id', name: 'owner_id')]
    private Id $ownerId;

    #[ORM\Column(type: 'string', length: 20)]
    private string $type;

    #[ORM\Column(type: 'integer', nullable: true, name: 'slot_id')]
    private ?int $slotId = null;

    #[ORM\Column(type: 'integer', name: 'duration_months')]
    private int $durationMonths;

    #[ORM\Column(type: 'integer', name: 'price_byn')]
    private int $priceByn;

    #[ORM\Column(type: 'string', length: 30)]
    private string $status;

    #[ORM\Column(type: 'string', length: 20)]
    private string $source;

    #[ORM\Column(type: 'datetime_immutable', name: 'created_at')]
    private \DateTimeImmutable $createdAt;

    #[ORM\Column(type: 'datetime_immutable', nullable: true, name: 'activated_at')]
    private ?\DateTimeImmutable $activatedAt = null;

    #[ORM\Column(type: 'datetime_immutable', nullable: true, name: 'expires_at')]
    private ?\DateTimeImmutable $expiresAt = null;

    #[ORM\Column(type: 'datetime_immutable', nullable: true, name: 'reservation_expires_at')]
    private ?\DateTimeImmutable $reservationExpiresAt = null;

    #[ORM\Column(type: 'id', nullable: true, name: 'activated_by_admin_id')]
    private ?Id $activatedByAdminId = null;

    #[ORM\Column(type: 'text', nullable: true)]
    private ?string $note = null;

    public function __construct(
        int $propertyId,
        Id $ownerId,
        string $type,
        int $durationMonths,
        int $priceByn,
        string $source,
        ?int $slotId = null,
        ?\DateTimeImmutable $now = null,
    ) {
        if (!in_array($type, PlacementPurchaseType::values(), true)) {
            throw new DomainException('Неизвестный тип размещения');
        }
        if (!in_array($durationMonths, self::ALLOWED_DURATIONS, true)) {
            throw new DomainException('Допустимый срок: 1, 3, 6 или 12 месяцев');
        }
        if ($type === PlacementPurchaseType::Special->value && $slotId === null) {
            throw new DomainException('Для спецразмещения нужно выбрать диапазон позиций');
        }
        if ($type === PlacementPurchaseType::Standard->value && $slotId !== null) {
            throw new DomainException('Стандартное размещение не привязано к слоту');
        }
        if ($priceByn < 0) {
            throw new DomainException('Цена не может быть отрицательной');
        }

        $now ??= new \DateTimeImmutable();
        $this->propertyId = $propertyId;
        $this->ownerId = $ownerId;
        $this->type = $type;
        $this->slotId = $slotId;
        $this->durationMonths = $durationMonths;
        $this->priceByn = $priceByn;
        $this->status = PlacementPurchaseStatus::PendingPayment->value;
        $this->source = $source;
        $this->createdAt = $now;
        $this->reservationExpiresAt = $now->modify('+' . self::RESERVATION_HOURS . ' hours');
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getPropertyId(): int
    {
        return $this->propertyId;
    }

    public function getOwnerId(): Id
    {
        return $this->ownerId;
    }

    public function getType(): string
    {
        return $this->type;
    }

    public function getSlotId(): ?int
    {
        return $this->slotId;
    }

    public function getDurationMonths(): int
    {
        return $this->durationMonths;
    }

    public function getPriceByn(): int
    {
        return $this->priceByn;
    }

    public function getStatus(): string
    {
        return $this->status;
    }

    public function getSource(): string
    {
        return $this->source;
    }

    public function getCreatedAt(): \DateTimeImmutable
    {
        return $this->createdAt;
    }

    public function getActivatedAt(): ?\DateTimeImmutable
    {
        return $this->activatedAt;
    }

    public function getExpiresAt(): ?\DateTimeImmutable
    {
        return $this->expiresAt;
    }

    public function getReservationExpiresAt(): ?\DateTimeImmutable
    {
        return $this->reservationExpiresAt;
    }

    public function getActivatedByAdminId(): ?Id
    {
        return $this->activatedByAdminId;
    }

    public function getNote(): ?string
    {
        return $this->note;
    }

    public function setPropertyId(int $propertyId): void
    {
        $this->propertyId = $propertyId;
    }

    public function setType(string $type): void
    {
        if (!in_array($type, PlacementPurchaseType::values(), true)) {
            throw new DomainException('Неизвестный тип размещения');
        }
        $this->type = $type;
    }

    public function setSlotId(?int $slotId): void
    {
        $this->slotId = $slotId;
    }

    public function setDurationMonths(int $durationMonths): void
    {
        if (!in_array($durationMonths, self::ALLOWED_DURATIONS, true)) {
            throw new DomainException('Допустимый срок: 1, 3, 6 или 12 месяцев');
        }
        $this->durationMonths = $durationMonths;
    }

    public function setPriceByn(int $priceByn): void
    {
        if ($priceByn < 0) {
            throw new DomainException('Цена не может быть отрицательной');
        }
        $this->priceByn = $priceByn;
    }

    public function setNote(?string $note): void
    {
        $value = $note !== null ? trim($note) : null;
        $this->note = $value === '' ? null : $value;
    }

    public function isPendingPayment(): bool
    {
        return $this->status === PlacementPurchaseStatus::PendingPayment->value;
    }

    public function isActive(): bool
    {
        return $this->status === PlacementPurchaseStatus::Active->value;
    }

    public function isReservationValid(?\DateTimeImmutable $now = null): bool
    {
        if (!$this->isPendingPayment() || $this->reservationExpiresAt === null) {
            return false;
        }

        $now ??= new \DateTimeImmutable();

        return $this->reservationExpiresAt > $now;
    }

    public function activate(?Id $adminId = null, ?\DateTimeImmutable $now = null): void
    {
        if (!$this->isPendingPayment()) {
            throw new DomainException('Активировать можно только заявку, ожидающую оплаты');
        }

        $now ??= new \DateTimeImmutable();
        $this->status = PlacementPurchaseStatus::Active->value;
        $this->activatedAt = $now;
        $this->expiresAt = $now->modify('+' . $this->durationMonths . ' months');
        $this->reservationExpiresAt = null;
        $this->activatedByAdminId = $adminId;
    }

    public function reject(?string $note = null): void
    {
        if (!$this->isPendingPayment()) {
            throw new DomainException('Отклонить можно только заявку, ожидающую оплаты');
        }

        $this->status = PlacementPurchaseStatus::Rejected->value;
        $this->reservationExpiresAt = null;
        $this->setNote($note);
    }

    public function markExpired(): void
    {
        if ($this->status === PlacementPurchaseStatus::Expired->value) {
            return;
        }

        $this->status = PlacementPurchaseStatus::Expired->value;
        $this->reservationExpiresAt = null;
    }

    public function cancelReservation(): void
    {
        if (!$this->isPendingPayment()) {
            return;
        }

        $this->status = PlacementPurchaseStatus::Cancelled->value;
        $this->reservationExpiresAt = null;
    }
}
