<?php

declare(strict_types=1);

namespace App\Domain\User\Entity;

use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity]
#[ORM\Table(name: 'user_individual_profiles')]
class UserIndividualProfile
{
    #[ORM\Id]
    #[ORM\OneToOne(targetEntity: User::class)]
    #[ORM\JoinColumn(name: 'user_id', referencedColumnName: 'id', nullable: false, onDelete: 'CASCADE')]
    private User $user;

    #[ORM\Column(type: 'string', length: 100, name: 'last_name')]
    private string $lastName;

    #[ORM\Column(type: 'string', length: 100, name: 'first_name')]
    private string $firstName;

    #[ORM\Column(type: 'string', length: 100, name: 'middle_name', nullable: true)]
    private ?string $middleName = null;

    #[ORM\Column(type: 'string', length: 9)]
    private string $unp;

    #[ORM\Column(type: 'datetime_immutable', name: 'created_at')]
    private \DateTimeImmutable $createdAt;

    #[ORM\Column(type: 'datetime_immutable', name: 'updated_at')]
    private \DateTimeImmutable $updatedAt;

    public function __construct(
        User $user,
        string $lastName,
        string $firstName,
        ?string $middleName,
        string $unp,
    ) {
        $this->user = $user;
        $this->lastName = $lastName;
        $this->firstName = $firstName;
        $this->middleName = $middleName;
        $this->unp = $unp;
        $this->createdAt = new \DateTimeImmutable();
        $this->updatedAt = new \DateTimeImmutable();
    }

    public function getUser(): User
    {
        return $this->user;
    }

    public function getLastName(): string
    {
        return $this->lastName;
    }

    public function getFirstName(): string
    {
        return $this->firstName;
    }

    public function getMiddleName(): ?string
    {
        return $this->middleName;
    }

    public function getUnp(): string
    {
        return $this->unp;
    }

    public function update(
        string $lastName,
        string $firstName,
        ?string $middleName,
        string $unp,
    ): void {
        $this->lastName = $lastName;
        $this->firstName = $firstName;
        $this->middleName = $middleName;
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
