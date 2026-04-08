<?php

declare(strict_types=1);

namespace App\Domain\Favorite\Entity;

use App\Domain\Shared\ValueObject\Id;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity]
#[ORM\Table(name: 'favorites')]
#[ORM\UniqueConstraint(name: 'uniq_user_property', columns: ['user_id', 'property_id'])]
#[ORM\Index(columns: ['user_id', 'created_at'])]
class Favorite
{
    #[ORM\Id]
    #[ORM\Column(type: 'id')]
    #[ORM\GeneratedValue]
    private Id $id;

    #[ORM\Column(type: 'id', name: 'user_id')]
    private Id $userId;

    #[ORM\Column(type: 'id', name: 'property_id')]
    private Id $propertyId;

    #[ORM\Column(type: 'datetime_immutable', name: 'created_at')]
    private \DateTimeImmutable $createdAt;

    public function __construct(Id $userId, Id $propertyId)
    {
        $this->userId = $userId;
        $this->propertyId = $propertyId;
        $this->createdAt = new \DateTimeImmutable();
    }

    public function getId(): Id
    {
        return $this->id;
    }

    public function getUserId(): Id
    {
        return $this->userId;
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
