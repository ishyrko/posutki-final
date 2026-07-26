<?php

declare(strict_types=1);

namespace App\Domain\Favorite\Entity;

use App\Domain\Shared\ValueObject\Id;
use Doctrine\ORM\Mapping as ORM;
use InvalidArgumentException;

#[ORM\Entity]
#[ORM\Table(name: 'favorites')]
#[ORM\UniqueConstraint(name: 'uniq_user_property', columns: ['user_id', 'property_id'])]
#[ORM\UniqueConstraint(name: 'uniq_visitor_property', columns: ['visitor_id', 'property_id'])]
#[ORM\Index(columns: ['user_id', 'created_at'])]
class Favorite
{
    #[ORM\Id]
    #[ORM\Column(type: 'id')]
    #[ORM\GeneratedValue]
    private Id $id;

    #[ORM\Column(type: 'id', name: 'user_id', nullable: true)]
    private ?Id $userId = null;

    #[ORM\Column(type: 'string', length: 64, name: 'visitor_id', nullable: true)]
    private ?string $visitorId = null;

    #[ORM\Column(type: 'id', name: 'property_id')]
    private Id $propertyId;

    #[ORM\Column(type: 'datetime_immutable', name: 'created_at')]
    private \DateTimeImmutable $createdAt;

    private function __construct(Id $propertyId)
    {
        $this->propertyId = $propertyId;
        $this->createdAt = new \DateTimeImmutable();
    }

    public static function forUser(Id $userId, Id $propertyId): self
    {
        $favorite = new self($propertyId);
        $favorite->userId = $userId;

        return $favorite;
    }

    public static function forVisitor(string $visitorId, Id $propertyId): self
    {
        $normalizedVisitorId = self::normalizeVisitorId($visitorId);
        $favorite = new self($propertyId);
        $favorite->visitorId = $normalizedVisitorId;

        return $favorite;
    }

    public function getId(): Id
    {
        return $this->id;
    }

    public function getUserId(): ?Id
    {
        return $this->userId;
    }

    public function getVisitorId(): ?string
    {
        return $this->visitorId;
    }

    public function getPropertyId(): Id
    {
        return $this->propertyId;
    }

    public function getCreatedAt(): \DateTimeImmutable
    {
        return $this->createdAt;
    }

    public static function normalizeVisitorId(string $visitorId): string
    {
        $visitorId = trim($visitorId);
        if ($visitorId === '' || strlen($visitorId) > 64 || !preg_match('/^[a-zA-Z0-9_-]+$/', $visitorId)) {
            throw new InvalidArgumentException('Некорректный visitorId');
        }

        return $visitorId;
    }
}
