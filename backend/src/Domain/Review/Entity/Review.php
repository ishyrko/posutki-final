<?php

declare(strict_types=1);

namespace App\Domain\Review\Entity;

use App\Domain\Property\Entity\Property;
use App\Domain\Review\ValueObject\ReviewStatus;
use App\Domain\Shared\ValueObject\Id;
use App\Domain\User\Entity\User;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity]
#[ORM\Table(name: 'reviews')]
#[ORM\UniqueConstraint(name: 'uniq_review_property_author', columns: ['property_id', 'author_id'])]
#[ORM\Index(name: 'idx_reviews_property_status', columns: ['property_id', 'status'])]
class Review
{
    #[ORM\Id]
    #[ORM\Column(type: 'id')]
    #[ORM\GeneratedValue(strategy: 'IDENTITY')]
    private ?Id $id = null;

    #[ORM\ManyToOne(targetEntity: Property::class)]
    #[ORM\JoinColumn(name: 'property_id', referencedColumnName: 'id', nullable: false, onDelete: 'CASCADE')]
    private Property $property;

    #[ORM\ManyToOne(targetEntity: User::class)]
    #[ORM\JoinColumn(name: 'author_id', referencedColumnName: 'id', nullable: false, onDelete: 'CASCADE')]
    private User $author;

    #[ORM\Column(type: 'smallint')]
    private int $rating;

    #[ORM\Column(type: 'text', nullable: true)]
    private ?string $text = null;

    #[ORM\Column(type: 'string', length: 20, enumType: ReviewStatus::class)]
    private ReviewStatus $status;

    #[ORM\Column(type: 'string', length: 500, nullable: true, name: 'moderation_comment')]
    private ?string $moderationComment = null;

    #[ORM\Column(type: 'datetime_immutable', name: 'created_at')]
    private \DateTimeImmutable $createdAt;

    #[ORM\Column(type: 'datetime_immutable', name: 'updated_at')]
    private \DateTimeImmutable $updatedAt;

    public function __construct(Property $property, User $author, int $rating, ?string $text = null)
    {
        if ($rating < 1 || $rating > 5) {
            throw new \InvalidArgumentException('Оценка должна быть от 1 до 5');
        }

        $this->property = $property;
        $this->author = $author;
        $this->rating = $rating;
        $this->text = $text;
        $this->status = ReviewStatus::Pending;
        $now = new \DateTimeImmutable();
        $this->createdAt = $now;
        $this->updatedAt = $now;
    }

    public function getId(): ?Id
    {
        return $this->id;
    }

    public function getProperty(): Property
    {
        return $this->property;
    }

    public function getAuthor(): User
    {
        return $this->author;
    }

    public function getRating(): int
    {
        return $this->rating;
    }

    public function getText(): ?string
    {
        return $this->text;
    }

    public function getStatus(): ReviewStatus
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

    public function getUpdatedAt(): \DateTimeImmutable
    {
        return $this->updatedAt;
    }

    public function approve(): void
    {
        $this->status = ReviewStatus::Approved;
        $this->updatedAt = new \DateTimeImmutable();
    }

    public function reject(?string $moderationComment = null): void
    {
        $this->status = ReviewStatus::Rejected;
        $this->moderationComment = $moderationComment;
        $this->updatedAt = new \DateTimeImmutable();
    }

    /** Повторная отправка после отклонения модератором. */
    public function resubmitToPending(int $rating, ?string $text): void
    {
        if ($rating < 1 || $rating > 5) {
            throw new \InvalidArgumentException('Оценка должна быть от 1 до 5');
        }

        $this->rating = $rating;
        $this->text = $text;
        $this->status = ReviewStatus::Pending;
        $this->moderationComment = null;
        $this->updatedAt = new \DateTimeImmutable();
    }

    public function isOwnedBy(Id $userId): bool
    {
        return $this->author->getId()->equals($userId);
    }
}
