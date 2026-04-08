<?php

declare(strict_types=1);

namespace App\Application\Query\User\GetUserPhones;

use App\Domain\Shared\ValueObject\Id;
use App\Domain\User\Repository\UserPhoneRepositoryInterface;

final class GetUserPhonesHandler
{
    public function __construct(
        private readonly UserPhoneRepositoryInterface $userPhoneRepository,
    ) {
    }

    public function __invoke(GetUserPhonesQuery $query): array
    {
        $userId = Id::fromString($query->userId);
        $phones = $this->userPhoneRepository->findByUserId($userId);

        return array_map(fn($phone) => [
            'id' => $phone->getId()->getValue(),
            'phone' => $phone->getPhone(),
            'isVerified' => $phone->isVerified(),
            'createdAt' => $phone->getCreatedAt()->format('c'),
        ], $phones);
    }
}
