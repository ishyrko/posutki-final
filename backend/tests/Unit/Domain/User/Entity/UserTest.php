<?php

declare(strict_types=1);

namespace App\Tests\Unit\Domain\User\Entity;

use App\Domain\Shared\ValueObject\Id;
use App\Domain\User\Entity\User;
use App\Domain\User\ValueObject\Email;
use PHPUnit\Framework\TestCase;

final class UserTest extends TestCase
{
    public function testRegisterCreatesRegularUser(): void
    {
        $user = User::register(
            Email::fromString('user@example.com'),
            'hashed-password',
            'Ivan',
            'Petrov'
        );

        self::assertSame('user@example.com', $user->getEmail()?->getValue());
        self::assertContains('ROLE_USER', $user->getRoles());
        self::assertFalse($user->isVerified());
    }

    public function testRegisterViaGoogleCreatesVerifiedUserWithoutPasswordFromInput(): void
    {
        $user = User::registerViaGoogle(
            Email::fromString('google@example.com'),
            'google-id-1',
            'Ivan',
            'Petrov',
            'https://example.com/avatar.jpg'
        );

        self::assertSame('google-id-1', $user->getGoogleId());
        self::assertTrue($user->isVerified());
        self::assertNotSame('', $user->getPassword());
    }

    public function testRegisterViaPhoneCreatesUserWithoutEmail(): void
    {
        $user = User::registerViaPhone('+375291112233', 'Ivan', 'Petrov');

        self::assertNull($user->getEmail());
        self::assertSame('+375291112233', $user->getPhone());
        self::assertTrue($user->isPhoneVerified());
    }

    public function testRequestPasswordResetGeneratesNewTokenEachTime(): void
    {
        $user = User::register(
            Email::fromString('reset@example.com'),
            'hashed-password',
            'Ivan',
            'Petrov'
        );

        $firstToken = $user->requestPasswordReset();
        $secondToken = $user->requestPasswordReset();

        self::assertNotSame($firstToken, $secondToken);
        self::assertTrue($user->isResetPasswordTokenValid($secondToken));
    }

    public function testIsResetPasswordTokenValidReturnsFalseForExpiredToken(): void
    {
        $user = User::register(
            Email::fromString('expired@example.com'),
            'hashed-password',
            'Ivan',
            'Petrov'
        );

        $token = $user->requestPasswordReset();

        $this->setPrivateProperty(
            $user,
            'resetPasswordTokenExpiresAt',
            new \DateTimeImmutable('-1 minute')
        );

        self::assertFalse($user->isResetPasswordTokenValid($token));
    }

    public function testVerifyMarksUserAsVerified(): void
    {
        $user = User::register(
            Email::fromString('verify@example.com'),
            'hashed-password',
            'Ivan',
            'Petrov'
        );

        $user->verify();

        self::assertTrue($user->isVerified());
    }

    public function testGetUserIdentifierFallsBackEmailThenPhoneThenId(): void
    {
        $emailUser = User::register(
            Email::fromString('id@example.com'),
            'hashed-password',
            'Ivan',
            'Petrov'
        );
        self::assertSame('id@example.com', $emailUser->getUserIdentifier());

        $phoneUser = User::registerViaPhone('+375299998877');
        self::assertSame('+375299998877', $phoneUser->getUserIdentifier());

        $this->setPrivateProperty($phoneUser, 'phone', null);
        $this->setPrivateProperty($phoneUser, 'id', Id::fromInt(777));

        self::assertSame('777', $phoneUser->getUserIdentifier());
    }

    private function setPrivateProperty(object $object, string $name, mixed $value): void
    {
        $reflection = new \ReflectionProperty($object, $name);
        $reflection->setAccessible(true);
        $reflection->setValue($object, $value);
    }
}
