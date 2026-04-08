<?php

declare(strict_types=1);

namespace App\Domain\Property\Entity;

use App\Domain\Shared\Exception\DomainException;
use App\Domain\Shared\ValueObject\Id;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity]
#[ORM\Table(name: 'property_revisions')]
#[ORM\Index(columns: ['property_id', 'status'], name: 'idx_property_revisions_property_status')]
class PropertyRevision
{
    public const STATUS_PENDING = 'pending';
    public const STATUS_APPROVED = 'approved';
    public const STATUS_REJECTED = 'rejected';

    #[ORM\Id]
    #[ORM\Column(type: 'id')]
    #[ORM\GeneratedValue]
    private Id $id;

    #[ORM\ManyToOne(targetEntity: Property::class, inversedBy: 'revisions')]
    #[ORM\JoinColumn(name: 'property_id', referencedColumnName: 'id', nullable: false, onDelete: 'CASCADE')]
    private Property $property;

    /** @var array<string, mixed> */
    #[ORM\Column(type: 'json')]
    private array $data;

    #[ORM\Column(type: 'string', length: 50)]
    private string $status = self::STATUS_PENDING;

    #[ORM\Column(type: 'text', nullable: true, name: 'moderation_comment')]
    private ?string $moderationComment = null;

    #[ORM\Column(type: 'datetime_immutable', name: 'created_at')]
    private \DateTimeImmutable $createdAt;

    #[ORM\Column(type: 'datetime_immutable', nullable: true, name: 'reviewed_at')]
    private ?\DateTimeImmutable $reviewedAt = null;

    /**
     * @param array<string, mixed> $data
     */
    public function __construct(Property $property, array $data = [])
    {
        $this->property = $property;
        $this->data = $data;
        $this->createdAt = new \DateTimeImmutable();
    }

    public function getId(): Id
    {
        return $this->id;
    }

    public function getProperty(): Property
    {
        return $this->property;
    }

    /**
     * @return array<string, mixed>
     */
    public function getData(): array
    {
        return $this->data;
    }

    /**
     * @param array<string, mixed> $data
     */
    public function setData(array $data): void
    {
        $this->data = $data;
        $this->status = self::STATUS_PENDING;
        $this->moderationComment = null;
        $this->reviewedAt = null;
    }

    public function getStatus(): string
    {
        return $this->status;
    }

    public function getModerationComment(): ?string
    {
        return $this->moderationComment;
    }

    public function getCreatedAt(): \DateTimeImmutable
    {
        return $this->createdAt;
    }

    public function getReviewedAt(): ?\DateTimeImmutable
    {
        return $this->reviewedAt;
    }

    public function approve(): void
    {
        if ($this->status !== self::STATUS_PENDING) {
            throw new DomainException('Одобрить можно только ожидающие версии');
        }

        $this->status = self::STATUS_APPROVED;
        $this->moderationComment = null;
        $this->reviewedAt = new \DateTimeImmutable();
    }

    public function reject(?string $comment = null): void
    {
        if ($this->status !== self::STATUS_PENDING) {
            throw new DomainException('Отклонить можно только ожидающие версии');
        }

        $this->status = self::STATUS_REJECTED;
        $this->moderationComment = $comment !== null ? trim($comment) : null;
        $this->reviewedAt = new \DateTimeImmutable();
    }
}
