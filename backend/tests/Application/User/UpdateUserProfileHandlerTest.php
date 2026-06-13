<?php

declare(strict_types=1);

namespace App\Tests\Application\User;

use App\Application\Command\User\UpdateUserProfile\UpdateUserProfileCommand;
use App\Application\Command\User\UpdateUserProfile\UpdateUserProfileHandler;
use App\Domain\Shared\Exception\DomainException;
use App\Domain\Shared\ValueObject\Id;
use App\Domain\User\Entity\User;
use App\Domain\User\Repository\UserPhoneRepositoryInterface;
use App\Domain\User\Repository\UserRepositoryInterface;
use PHPUnit\Framework\TestCase;

final class UpdateUserProfileHandlerTest extends TestCase
{
    public function testUpdatesTelegramWithoutChangingNameWhenNameIsNotProvided(): void
    {
        $user = User::registerViaPhone('+375291112233', 'Иван', 'Петров');
        $this->setUserId($user, 42);

        $userRepository = $this->createMock(UserRepositoryInterface::class);
        $userRepository->method('findById')->willReturn($user);
        $userRepository->expects(self::once())->method('save')->with($user);

        $handler = new UpdateUserProfileHandler(
            $userRepository,
            $this->createStub(UserPhoneRepositoryInterface::class),
        );

        ($handler)(new UpdateUserProfileCommand(
            userId: '42',
            name: null,
            phone: null,
            avatar: null,
            telegram: 't.me/tests',
            phoneHasViber: false,
            phoneHasWhatsapp: false,
        ));

        self::assertSame('Иван', $user->getFirstName());
        self::assertSame('Петров', $user->getLastName());
        self::assertSame('tests', $user->getTelegram());
    }

    public function testUpdatesNameWhenProvided(): void
    {
        $user = User::registerViaPhone('+375291112233', 'Иван', 'Петров');
        $this->setUserId($user, 42);

        $userRepository = $this->createMock(UserRepositoryInterface::class);
        $userRepository->method('findById')->willReturn($user);
        $userRepository->expects(self::once())->method('save')->with($user);

        $handler = new UpdateUserProfileHandler(
            $userRepository,
            $this->createStub(UserPhoneRepositoryInterface::class),
        );

        ($handler)(new UpdateUserProfileCommand(
            userId: '42',
            name: 'Новое имя',
            phone: null,
            avatar: null,
        ));

        self::assertSame('Новое имя', $user->getFirstName());
        self::assertSame('', $user->getLastName());
    }

    public function testRejectsInvalidTelegram(): void
    {
        $user = User::registerViaPhone('+375291112233');
        $this->setUserId($user, 42);

        $userRepository = $this->createMock(UserRepositoryInterface::class);
        $userRepository->method('findById')->willReturn($user);
        $userRepository->expects(self::never())->method('save');

        $handler = new UpdateUserProfileHandler(
            $userRepository,
            $this->createStub(UserPhoneRepositoryInterface::class),
        );

        $this->expectException(DomainException::class);
        $this->expectExceptionMessage('Ник Telegram: от 5 до 32 символов');

        ($handler)(new UpdateUserProfileCommand(
            userId: '42',
            name: null,
            phone: null,
            avatar: null,
            telegram: 'abcd',
        ));
    }

    private function setUserId(User $user, int $id): void
    {
        $reflection = new \ReflectionProperty($user, 'id');
        $reflection->setAccessible(true);
        $reflection->setValue($user, Id::fromInt($id));
    }
}
