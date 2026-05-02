<?php

declare(strict_types=1);

namespace App\Application\Service;

use App\Domain\Shared\Exception\DomainException;
use App\Domain\Shared\ValueObject\Id;
use App\Domain\User\Repository\UserPhoneRepositoryInterface;
use App\Domain\User\Repository\UserRepositoryInterface;

readonly class PropertyOwnerPublicContactResolver
{
    public function __construct(
        private UserRepositoryInterface $userRepository,
        private UserPhoneRepositoryInterface $userPhoneRepository,
    ) {
    }

    /**
     * @param list<int> $ownerIds
     * @return array<int, array{phone: ?string, name: ?string}>
     */
    public function resolveForOwnerIds(array $ownerIds): array
    {
        $ownerIds = array_values(array_unique(array_filter(
            $ownerIds,
            static fn(mixed $id): bool => is_int($id) || (is_string($id) && $id !== '' && ctype_digit((string) $id)),
        )));
        if ($ownerIds === []) {
            return [];
        }

        $intIds = array_map(static fn(mixed $id): int => (int) $id, $ownerIds);

        $usersById = $this->userRepository->findByIds($intIds);
        $phonesByUserId = $this->userPhoneRepository->findPrimaryVerifiedPhonesByUserIds($intIds);

        $out = [];
        foreach ($intIds as $oid) {
            $user = $usersById[$oid] ?? null;
            if ($user === null) {
                $out[$oid] = ['phone' => null, 'name' => null];
                continue;
            }

            $name = trim($user->getFullName());
            $phone = $phonesByUserId[$oid] ?? null;
            if ($phone === null && $user->getPhone() !== null && $user->isPhoneVerified()) {
                $phone = $user->getPhone();
            }

            $out[$oid] = [
                'phone' => $phone !== null && $phone !== '' ? $phone : null,
                'name' => $name !== '' ? $name : null,
            ];
        }

        return $out;
    }

    public function assertOwnerHasPublicContact(string $ownerIdString): void
    {
        $id = Id::fromString($ownerIdString)->getValue();
        $resolved = $this->resolveForOwnerIds([$id])[$id] ?? ['phone' => null, 'name' => null];

        if ($resolved['phone'] === null) {
            throw new DomainException('Подтвердите телефон в профиле');
        }
        if ($resolved['name'] === null) {
            throw new DomainException('Укажите имя и фамилию в профиле');
        }
    }
}
