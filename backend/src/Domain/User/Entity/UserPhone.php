<?php

declare(strict_types=1);

namespace App\Domain\User\Entity;

use App\Domain\Shared\ValueObject\Id;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity]
#[ORM\Table(name: 'user_phones')]
#[ORM\UniqueConstraint(columns: ['user_id', 'phone'])]
class UserPhone
{
    #[ORM\Id]
    #[ORM\Column(type: 'id')]
    #[ORM\GeneratedValue]
    private Id $id;

    #[ORM\Column(type: 'id', name: 'user_id')]
    private Id $userId;

    #[ORM\Column(type: 'string', length: 20)]
    private string $phone;

    #[ORM\Column(type: 'boolean', name: 'is_verified')]
    private bool $isVerified = false;

    #[ORM\Column(type: 'string', length: 6, nullable: true)]
    private ?string $code = null;

    #[ORM\Column(type: 'datetime_immutable', nullable: true, name: 'code_expires_at')]
    private ?\DateTimeImmutable $codeExpiresAt = null;

    #[ORM\Column(type: 'datetime_immutable', name: 'created_at')]
    private \DateTimeImmutable $createdAt;

    public function __construct(Id $userId, string $phone)
    {
        $this->userId = $userId;
        $this->phone = $phone;
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

    public function getPhone(): string
    {
        return $this->phone;
    }

    public function isVerified(): bool
    {
        return $this->isVerified;
    }

    public function getCode(): ?string
    {
        return $this->code;
    }

    public function getCodeExpiresAt(): ?\DateTimeImmutable
    {
        return $this->codeExpiresAt;
    }

    public function getCreatedAt(): \DateTimeImmutable
    {
        return $this->createdAt;
    }

    public function setVerificationCode(string $code, \DateTimeImmutable $expiresAt): void
    {
        $this->code = $code;
        $this->codeExpiresAt = $expiresAt;
    }

    public function verify(string $code): bool
    {
        if ($this->code === null || $this->codeExpiresAt === null) {
            return false;
        }

        if ($this->codeExpiresAt < new \DateTimeImmutable()) {
            return false;
        }

        if ($this->code !== $code) {
            return false;
        }

        $this->isVerified = true;
        $this->code = null;
        $this->codeExpiresAt = null;

        return true;
    }
}
