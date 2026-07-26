<?php

declare(strict_types=1);

namespace App\Domain\Favorite\Entity;

use App\Domain\Shared\ValueObject\Id;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity]
#[ORM\Table(name: 'favorite_add_events')]
#[ORM\Index(columns: ['property_id', 'created_at'], name: 'idx_favorite_add_events_property_created')]
class FavoriteAddEvent
{
    #[ORM\Id]
    #[ORM\Column(type: 'integer')]
    #[ORM\GeneratedValue]
    private int $id;

    #[ORM\Column(type: 'id', name: 'property_id')]
    private Id $propertyId;

    #[ORM\Column(type: 'datetime_immutable', name: 'created_at')]
    private \DateTimeImmutable $createdAt;

    private function __construct(Id $propertyId, \DateTimeImmutable $createdAt)
    {
        $this->propertyId = $propertyId;
        $this->createdAt = $createdAt;
    }

    public static function create(Id $propertyId, ?\DateTimeImmutable $createdAt = null): self
    {
        return new self($propertyId, $createdAt ?? new \DateTimeImmutable());
    }

    public function getPropertyId(): Id
    {
        return $this->propertyId;
    }

    public function getCreatedAt(): \DateTimeImmutable
    {
        return $this->createdAt;
    }
}
