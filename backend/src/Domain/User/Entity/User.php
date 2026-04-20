<?php

declare(strict_types=1);

namespace App\Domain\User\Entity;

use App\Domain\Shared\Exception\DomainException;
use App\Domain\Shared\ValueObject\Id;
use App\Domain\User\ValueObject\Email;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Security\Core\User\UserInterface;
use Symfony\Component\Security\Core\User\PasswordAuthenticatedUserInterface;

#[ORM\Entity]
#[ORM\Table(name: 'users')]
class User implements UserInterface, PasswordAuthenticatedUserInterface
{
    #[ORM\Id]
    #[ORM\Column(type: 'id')]
    #[ORM\GeneratedValue(strategy: 'IDENTITY')]
    private Id $id;

    #[ORM\Column(type: 'email', length: 180, unique: true, nullable: true)]
    private ?Email $email;

    #[ORM\Column(type: 'string')]
    private string $password;

    #[ORM\Column(type: 'string', length: 100, name: 'first_name')]
    private string $firstName;

    #[ORM\Column(type: 'string', length: 100, name: 'last_name')]
    private string $lastName;

    #[ORM\Column(type: 'string', length: 20, nullable: true)]
    private ?string $phone = null;

    #[ORM\Column(type: 'string', length: 255, nullable: true)]
    private ?string $avatar = null;

    #[ORM\Column(type: 'datetime_immutable', name: 'created_at')]
    private \DateTimeImmutable $createdAt;

    #[ORM\Column(type: 'datetime_immutable', name: 'updated_at')]
    private \DateTimeImmutable $updatedAt;

    #[ORM\Column(type: 'boolean', name: 'is_verified')]
    private bool $isVerified = false;

    #[ORM\Column(type: 'string', length: 255, nullable: true, name: 'google_id')]
    private ?string $googleId = null;

    #[ORM\Column(type: 'boolean', name: 'is_phone_verified')]
    private bool $isPhoneVerified = false;

    #[ORM\Column(type: 'json')]
    private array $roles = ['ROLE_USER'];

    #[ORM\Column(type: 'string', length: 64, nullable: true, name: 'reset_password_token')]
    private ?string $resetPasswordToken = null;

    #[ORM\Column(type: 'datetime_immutable', nullable: true, name: 'reset_password_token_expires_at')]
    private ?\DateTimeImmutable $resetPasswordTokenExpiresAt = null;

    #[ORM\Column(type: 'string', length: 64, nullable: true, name: 'email_verification_token')]
    private ?string $emailVerificationToken = null;

    #[ORM\Column(type: 'datetime_immutable', nullable: true, name: 'email_verification_token_expires_at')]
    private ?\DateTimeImmutable $emailVerificationTokenExpiresAt = null;

    #[ORM\Column(type: 'string', length: 180, nullable: true, unique: true, name: 'pending_email')]
    private ?string $pendingEmail = null;

    public function __construct(
        ?Email $email,
        string $password,
        string $firstName,
        string $lastName,
        ?string $phone = null,
        ?string $avatar = null
    ) {
        $this->email = $email;
        $this->password = $password;
        $this->firstName = $firstName;
        $this->lastName = $lastName;
        $this->phone = $phone;
        $this->avatar = $avatar;
        $this->createdAt = new \DateTimeImmutable();
        $this->updatedAt = new \DateTimeImmutable();
    }

    public static function register(
        Email $email,
        string $hashedPassword,
        string $firstName,
        string $lastName
    ): self {
        return new self($email, $hashedPassword, $firstName, $lastName);
    }

    public static function registerViaGoogle(
        Email $email,
        string $googleId,
        string $firstName,
        string $lastName,
        ?string $avatar = null
    ): self {
        $randomPassword = bin2hex(random_bytes(32));
        $user = new self($email, $randomPassword, $firstName, $lastName, null, $avatar);
        $user->googleId = $googleId;
        $user->isVerified = true;
        return $user;
    }

    public static function registerViaPhone(
        string $phone,
        ?string $firstName = null,
        ?string $lastName = null,
    ): self {
        $randomPassword = bin2hex(random_bytes(32));

        $user = new self(
            null,
            $randomPassword,
            trim($firstName ?? ''),
            trim($lastName ?? ''),
            $phone,
        );
        $user->isPhoneVerified = true;

        return $user;
    }

    public function getId(): Id
    {
        return $this->id;
    }

    public function getEmail(): ?Email
    {
        return $this->email;
    }

    public function getPassword(): string
    {
        return $this->password;
    }

    public function getFirstName(): string
    {
        return $this->firstName;
    }

    public function getLastName(): string
    {
        return $this->lastName;
    }

    public function getFullName(): string
    {
        return $this->firstName . ' ' . $this->lastName;
    }

    public function getRoles(): array
    {
        return $this->roles;
    }

    public function isVerified(): bool
    {
        return $this->isVerified;
    }

    // Domain Methods
    public function verify(): void
    {
        $this->isVerified = true;
        $this->updatedAt = new \DateTimeImmutable();
    }

    public function requestEmailVerification(): string
    {
        if ($this->email === null) {
            throw new DomainException('Нельзя запросить подтверждение email без адреса');
        }

        $this->emailVerificationToken = bin2hex(random_bytes(32));
        $this->emailVerificationTokenExpiresAt = new \DateTimeImmutable('+24 hours');
        $this->updatedAt = new \DateTimeImmutable();

        return $this->emailVerificationToken;
    }

    public function isEmailVerificationTokenValid(string $token): bool
    {
        return $this->emailVerificationToken === $token
            && $this->emailVerificationTokenExpiresAt !== null
            && $this->emailVerificationTokenExpiresAt > new \DateTimeImmutable();
    }

    public function confirmEmailVerification(string $token): void
    {
        if ($this->email === null) {
            throw new DomainException('Нет email для подтверждения');
        }
        if (!$this->isEmailVerificationTokenValid($token)) {
            throw new DomainException('Недействительная или просроченная ссылка подтверждения email');
        }
        $this->isVerified = true;
        $this->emailVerificationToken = null;
        $this->emailVerificationTokenExpiresAt = null;
        $this->updatedAt = new \DateTimeImmutable();
    }

    /**
     * @return string Verification token
     */
    public function setPendingEmail(Email $email): string
    {
        $this->pendingEmail = $email->getValue();
        $this->emailVerificationToken = bin2hex(random_bytes(32));
        $this->emailVerificationTokenExpiresAt = new \DateTimeImmutable('+24 hours');
        $this->updatedAt = new \DateTimeImmutable();

        return $this->emailVerificationToken;
    }

    public function getPendingEmail(): ?Email
    {
        return $this->pendingEmail !== null ? Email::fromString($this->pendingEmail) : null;
    }

    public function confirmPendingEmail(string $token): void
    {
        if ($this->pendingEmail === null) {
            throw new DomainException('Нет ожидающего подтверждения email');
        }
        if (!$this->isEmailVerificationTokenValid($token)) {
            throw new DomainException('Недействительная или просроченная ссылка подтверждения email');
        }
        $this->email = Email::fromString($this->pendingEmail);
        $this->pendingEmail = null;
        $this->isVerified = true;
        $this->emailVerificationToken = null;
        $this->emailVerificationTokenExpiresAt = null;
        $this->updatedAt = new \DateTimeImmutable();
    }

    public function updateProfile(string $firstName, string $lastName, ?string $phone = null, ?string $avatar = null): void
    {
        $this->firstName = $firstName;
        $this->lastName = $lastName;
        if ($this->phone !== $phone) {
            $this->isPhoneVerified = false;
        }
        $this->phone = $phone;
        if ($avatar !== null) {
            $this->avatar = $avatar;
        }
        $this->updatedAt = new \DateTimeImmutable();
    }

    public function changePassword(string $newHashedPassword): void
    {
        $this->password = $newHashedPassword;
        $this->resetPasswordToken = null;
        $this->resetPasswordTokenExpiresAt = null;
        $this->updatedAt = new \DateTimeImmutable();
    }

    public function requestPasswordReset(): string
    {
        $this->resetPasswordToken = bin2hex(random_bytes(32));
        $this->resetPasswordTokenExpiresAt = new \DateTimeImmutable('+1 hour');
        $this->updatedAt = new \DateTimeImmutable();

        return $this->resetPasswordToken;
    }

    public function getResetPasswordToken(): ?string
    {
        return $this->resetPasswordToken;
    }

    public function isResetPasswordTokenValid(string $token): bool
    {
        return $this->resetPasswordToken === $token
            && $this->resetPasswordTokenExpiresAt !== null
            && $this->resetPasswordTokenExpiresAt > new \DateTimeImmutable();
    }

    public function isPhoneVerified(): bool
    {
        return $this->isPhoneVerified;
    }

    public function markPhoneVerified(): void
    {
        $this->isPhoneVerified = true;
        $this->updatedAt = new \DateTimeImmutable();
    }

    /**
     * Sets main profile phone when it was empty and marks it verified (e.g. SMS verification flow).
     */
    public function setVerifiedProfilePhone(string $phone): void
    {
        $this->phone = $phone;
        $this->isPhoneVerified = true;
        $this->updatedAt = new \DateTimeImmutable();
    }

    public function getPhone(): ?string
    {
        return $this->phone;
    }

    public function getAvatar(): ?string
    {
        return $this->avatar;
    }

    public function getGoogleId(): ?string
    {
        return $this->googleId;
    }

    public function setGoogleId(string $googleId): void
    {
        $this->googleId = $googleId;
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

    // Symfony UserInterface methods
    public function getUserIdentifier(): string
    {
        if ($this->email !== null) {
            return $this->email->getValue();
        }

        if ($this->phone !== null) {
            return $this->phone;
        }

        return (string) $this->id->getValue();
    }

    public function eraseCredentials(): void
    {
        // Nothing to do - we don't store plain password
    }

    public function grantRole(string $role): void
    {
        if (!in_array($role, $this->roles, true)) {
            $this->roles[] = $role;
            $this->updatedAt = new \DateTimeImmutable();
        }
    }

    public function __toString(): string
    {
        return $this->getFullName();
    }
}