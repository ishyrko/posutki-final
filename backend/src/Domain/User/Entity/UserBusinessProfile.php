<?php

declare(strict_types=1);

namespace App\Domain\User\Entity;

use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity]
#[ORM\Table(name: 'user_business_profiles')]
class UserBusinessProfile
{
    #[ORM\Id]
    #[ORM\OneToOne(targetEntity: User::class)]
    #[ORM\JoinColumn(name: 'user_id', referencedColumnName: 'id', nullable: false, onDelete: 'CASCADE')]
    private User $user;

    #[ORM\Column(type: 'string', length: 255, name: 'organization_name')]
    private string $organizationName;

    #[ORM\Column(type: 'string', length: 200, name: 'contact_name')]
    private string $contactName;

    #[ORM\Column(type: 'string', length: 9)]
    private string $unp;

    #[ORM\Column(type: 'datetime_immutable', name: 'created_at')]
    private \DateTimeImmutable $createdAt;

    #[ORM\Column(type: 'datetime_immutable', name: 'updated_at')]
    private \DateTimeImmutable $updatedAt;

    public function __construct(
        User $user,
        string $organizationName,
        string $contactName,
        string $unp,
    ) {
        $this->user = $user;
        $this->organizationName = $organizationName;
        $this->contactName = $contactName;
        $this->unp = $unp;
        $this->createdAt = new \DateTimeImmutable();
        $this->updatedAt = new \DateTimeImmutable();
    }

    public function getUser(): User
    {
        return $this->user;
    }

    public function getOrganizationName(): string
    {
        return $this->organizationName;
    }

    public function getContactName(): string
    {
        return $this->contactName;
    }

    public function getUnp(): string
    {
        return $this->unp;
    }

    public function update(
        string $organizationName,
        string $contactName,
        string $unp,
    ): void {
        $this->organizationName = $organizationName;
        $this->contactName = $contactName;
        $this->unp = $unp;
        $this->updatedAt = new \DateTimeImmutable();
    }

    public function getCreatedAt(): \DateTimeImmutable
    {
        return $this->createdAt;
    }

    public function getUpdatedAt(): \DateTimeImmutable
    {
        return $this->updatedAt;
    }
}
