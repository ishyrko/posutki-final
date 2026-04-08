<?php

declare(strict_types=1);

namespace App\Domain\User\Entity;

use App\Domain\Shared\ValueObject\Id;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity]
#[ORM\Table(name: 'auth_phone_codes')]
#[ORM\UniqueConstraint(columns: ['phone'])]
class PhoneAuthCode
{
    #[ORM\Id]
    #[ORM\Column(type: 'id')]
    #[ORM\GeneratedValue]
    private Id $id;

    #[ORM\Column(type: 'string', length: 20)]
    private string $phone;

    #[ORM\Column(type: 'string', length: 6)]
    private string $code;

    #[ORM\Column(type: 'datetime_immutable', name: 'expires_at')]
    private \DateTimeImmutable $expiresAt;

    #[ORM\Column(type: 'datetime_immutable', name: 'created_at')]
    private \DateTimeImmutable $createdAt;

    #[ORM\Column(type: 'datetime_immutable', name: 'updated_at')]
    private \DateTimeImmutable $updatedAt;

    public function __construct(string $phone, string $code, \DateTimeImmutable $expiresAt)
    {
        $this->phone = $phone;
        $this->code = $code;
        $this->expiresAt = $expiresAt;
        $this->createdAt = new \DateTimeImmutable();
        $this->updatedAt = $this->createdAt;
    }

    public function getId(): Id
    {
        return $this->id;
    }

    public function getPhone(): string
    {
        return $this->phone;
    }

    public function refreshCode(string $code, \DateTimeImmutable $expiresAt): void
    {
        $this->code = $code;
        $this->expiresAt = $expiresAt;
        $this->updatedAt = new \DateTimeImmutable();
    }

    public function verify(string $code): bool
    {
        if ($this->expiresAt < new \DateTimeImmutable()) {
            return false;
        }

        return hash_equals($this->code, $code);
    }
}
