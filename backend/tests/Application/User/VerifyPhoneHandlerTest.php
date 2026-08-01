<?php

declare(strict_types=1);

namespace App\Tests\Application\User;

use App\Application\Command\User\VerifyPhone\VerifyPhoneCommand;
use App\Application\Command\User\VerifyPhone\VerifyPhoneHandler;
use App\Domain\Shared\Exception\ConflictException;
use App\Domain\Shared\ValueObject\Id;
use App\Domain\User\Entity\User;
use App\Domain\User\Entity\UserPhone;
use App\Domain\User\Repository\UserPhoneRepositoryInterface;
use App\Domain\User\Repository\UserRepositoryInterface;
use App\Domain\User\ValueObject\Email;
use PHPUnit\Framework\TestCase;

final class VerifyPhoneHandlerTest extends TestCase
{
    private const CODE = '123456';

    public function testVerifyingDifferentNumberPromotesItToVerifiedMainWhenMainIsUnverified(): void
    {
        $userId = Id::fromInt(42);
        $user = User::register(Email::fromString('user@example.com'), 'hash', 'Иван', 'Петров');
        $user->updateProfile('Иван', 'Петров', '+375291111111');
        $this->setUserId($user, 42);

        $userPhone = $this->createPendingUserPhone($userId, '+375292222222');

        $userRepository = $this->createMock(UserRepositoryInterface::class);
        $userRepository->method('findVerifiedByPhone')->willReturn(null);
        $userRepository->method('findById')->willReturn($user);
        $userRepository->expects(self::once())->method('save')->with($user);

        $userPhoneRepository = $this->createMock(UserPhoneRepositoryInterface::class);
        $userPhoneRepository
            ->method('findByUserIdAndPhone')
            ->with($userId, '+375292222222')
            ->willReturn($userPhone);
        $userPhoneRepository->expects(self::once())->method('save')->with($userPhone);

        $handler = new VerifyPhoneHandler($userPhoneRepository, $userRepository);

        ($handler)(new VerifyPhoneCommand(
            userId: '42',
            phone: '+375 29 222-22-22',
            code: self::CODE,
        ));

        self::assertTrue($userPhone->isVerified());
        self::assertSame('+375292222222', $user->getPhone());
        self::assertTrue($user->isPhoneVerified());
    }

    public function testVerifyingDifferentNumberDoesNotChangeVerifiedMainPhone(): void
    {
        $userId = Id::fromInt(42);
        $user = User::registerViaPhone('+375291111111', 'Иван', 'Петров');
        $this->setUserId($user, 42);

        $userPhone = $this->createPendingUserPhone($userId, '+375292222222');

        $userRepository = $this->createMock(UserRepositoryInterface::class);
        $userRepository->method('findVerifiedByPhone')->willReturn(null);
        $userRepository->method('findById')->willReturn($user);
        $userRepository->expects(self::never())->method('save');

        $userPhoneRepository = $this->createMock(UserPhoneRepositoryInterface::class);
        $userPhoneRepository
            ->method('findByUserIdAndPhone')
            ->with($userId, '+375292222222')
            ->willReturn($userPhone);
        $userPhoneRepository->expects(self::once())->method('save')->with($userPhone);

        $handler = new VerifyPhoneHandler($userPhoneRepository, $userRepository);

        ($handler)(new VerifyPhoneCommand(
            userId: '42',
            phone: '+375292222222',
            code: self::CODE,
        ));

        self::assertTrue($userPhone->isVerified());
        self::assertSame('+375291111111', $user->getPhone());
        self::assertTrue($user->isPhoneVerified());
    }

    public function testVerifyingPhoneSetsMainWhenMainIsEmpty(): void
    {
        $userId = Id::fromInt(42);
        $user = User::register(Email::fromString('user@example.com'), 'hash', 'Иван', 'Петров');
        $this->setUserId($user, 42);

        $userPhone = $this->createPendingUserPhone($userId, '+375292222222');

        $userRepository = $this->createMock(UserRepositoryInterface::class);
        $userRepository->method('findVerifiedByPhone')->willReturn(null);
        $userRepository->method('findById')->willReturn($user);
        $userRepository->expects(self::once())->method('save')->with($user);

        $userPhoneRepository = $this->createMock(UserPhoneRepositoryInterface::class);
        $userPhoneRepository
            ->method('findByUserIdAndPhone')
            ->with($userId, '+375292222222')
            ->willReturn($userPhone);
        $userPhoneRepository->expects(self::once())->method('save')->with($userPhone);

        $handler = new VerifyPhoneHandler($userPhoneRepository, $userRepository);

        ($handler)(new VerifyPhoneCommand(
            userId: '42',
            phone: '+375292222222',
            code: self::CODE,
        ));

        self::assertTrue($userPhone->isVerified());
        self::assertSame('+375292222222', $user->getPhone());
        self::assertTrue($user->isPhoneVerified());
    }

    public function testThrowsConflictWhenPhoneAlreadyVerifiedByAnotherUser(): void
    {
        $userId = Id::fromInt(42);
        $user = User::register(Email::fromString('user@example.com'), 'hash', 'Иван', 'Петров');
        $user->updateProfile('Иван', 'Петров', '+375291111111');
        $this->setUserId($user, 42);

        $otherUser = User::registerViaPhone('+375292222222', 'Другой', 'Пользователь');
        $this->setUserId($otherUser, 99);

        $userRepository = $this->createMock(UserRepositoryInterface::class);
        $userRepository
            ->method('findVerifiedByPhone')
            ->with('+375292222222')
            ->willReturn($otherUser);
        $userRepository->expects(self::never())->method('findById');
        $userRepository->expects(self::never())->method('save');

        $userPhoneRepository = $this->createStub(UserPhoneRepositoryInterface::class);

        $handler = new VerifyPhoneHandler($userPhoneRepository, $userRepository);

        $this->expectException(ConflictException::class);
        $this->expectExceptionMessage('Этот телефон уже подтверждён другим пользователем');

        ($handler)(new VerifyPhoneCommand(
            userId: '42',
            phone: '+375292222222',
            code: self::CODE,
        ));
    }

    private function createPendingUserPhone(Id $userId, string $phone): UserPhone
    {
        $userPhone = new UserPhone($userId, $phone);
        $userPhone->setVerificationCode(self::CODE, new \DateTimeImmutable('+10 minutes'));

        return $userPhone;
    }

    private function setUserId(User $user, int $id): void
    {
        $reflection = new \ReflectionProperty($user, 'id');
        $reflection->setAccessible(true);
        $reflection->setValue($user, Id::fromInt($id));
    }
}
