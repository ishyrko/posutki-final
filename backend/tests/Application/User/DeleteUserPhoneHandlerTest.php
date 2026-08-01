<?php

declare(strict_types=1);

namespace App\Tests\Application\User;

use App\Application\Command\User\DeleteUserPhone\DeleteUserPhoneCommand;
use App\Application\Command\User\DeleteUserPhone\DeleteUserPhoneHandler;
use App\Domain\Shared\Exception\DomainException;
use App\Domain\Shared\ValueObject\Id;
use App\Domain\User\Entity\UserPhone;
use App\Domain\User\Repository\UserPhoneRepositoryInterface;
use PHPUnit\Framework\TestCase;

final class DeleteUserPhoneHandlerTest extends TestCase
{
    public function testDeletesPhoneWhenUserIdMatchesAsString(): void
    {
        $userId = Id::fromInt(42);
        $phoneId = Id::fromInt(7);
        $userPhone = new UserPhone($userId, '+375292222222');
        $this->setEntityId($userPhone, 7);

        $userPhoneRepository = $this->createMock(UserPhoneRepositoryInterface::class);
        $userPhoneRepository->method('findById')->with($phoneId)->willReturn($userPhone);
        $userPhoneRepository->expects(self::once())->method('delete')->with($userPhone);

        $handler = new DeleteUserPhoneHandler($userPhoneRepository);

        ($handler)(new DeleteUserPhoneCommand(
            userId: '42',
            phoneId: '7',
        ));
    }

    public function testThrowsWhenPhoneBelongsToAnotherUser(): void
    {
        $userPhone = new UserPhone(Id::fromInt(99), '+375292222222');
        $this->setEntityId($userPhone, 7);

        $userPhoneRepository = $this->createMock(UserPhoneRepositoryInterface::class);
        $userPhoneRepository->method('findById')->willReturn($userPhone);
        $userPhoneRepository->expects(self::never())->method('delete');

        $handler = new DeleteUserPhoneHandler($userPhoneRepository);

        $this->expectException(DomainException::class);
        $this->expectExceptionMessage('Доступ запрещён');

        ($handler)(new DeleteUserPhoneCommand(
            userId: '42',
            phoneId: '7',
        ));
    }

    private function setEntityId(UserPhone $userPhone, int $id): void
    {
        $reflection = new \ReflectionProperty($userPhone, 'id');
        $reflection->setAccessible(true);
        $reflection->setValue($userPhone, Id::fromInt($id));
    }
}
