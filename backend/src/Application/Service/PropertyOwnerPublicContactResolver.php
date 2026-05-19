<?php

declare(strict_types=1);

namespace App\Application\Service;

use App\Domain\Shared\Exception\DomainException;
use App\Domain\Shared\ValueObject\Id;
use App\Domain\User\Repository\UserPhoneRepositoryInterface;
use App\Domain\User\Repository\UserRepositoryInterface;
use App\Domain\User\Service\PhoneNumberNormalizer;

readonly class PropertyOwnerPublicContactResolver
{
    public function __construct(
        private UserRepositoryInterface $userRepository,
        private UserPhoneRepositoryInterface $userPhoneRepository,
    ) {
    }

    /**
     * @param list<int> $ownerIds
     * @return array<int, array{
     *   phone: ?string,
     *   name: ?string,
     *   phones: list<array{phone: string, hasViber: bool, hasWhatsapp: bool}>,
     *   telegram: ?string
     * }>
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
        $extraPhonesByUserId = $this->userPhoneRepository->findVerifiedPhonesGroupedByUserIds($intIds);

        $out = [];
        foreach ($intIds as $oid) {
            $user = $usersById[$oid] ?? null;
            if ($user === null) {
                $out[$oid] = ['phone' => null, 'name' => null, 'phones' => [], 'telegram' => null];
                continue;
            }

            $name = trim($user->getFullName());
            $phones = [];
            $seen = [];

            if ($user->getPhone() !== null && $user->isPhoneVerified()) {
                $mainPhone = $user->getPhone();
                $phones[] = [
                    'phone' => $mainPhone,
                    'hasViber' => $user->isPhoneHasViber(),
                    'hasWhatsapp' => $user->isPhoneHasWhatsapp(),
                ];
                $seen[$this->normalizePhoneKey($mainPhone)] = true;
            }

            foreach ($extraPhonesByUserId[$oid] ?? [] as $userPhone) {
                $key = $this->normalizePhoneKey($userPhone->getPhone());
                if (isset($seen[$key])) {
                    continue;
                }
                $seen[$key] = true;
                $phones[] = [
                    'phone' => $userPhone->getPhone(),
                    'hasViber' => $userPhone->hasViber(),
                    'hasWhatsapp' => $userPhone->hasWhatsapp(),
                ];
            }

            $telegram = $user->getTelegram();
            $telegram = $telegram !== null && trim($telegram) !== '' ? trim($telegram) : null;

            $out[$oid] = [
                'phone' => $phones[0]['phone'] ?? null,
                'name' => $name !== '' ? $name : null,
                'phones' => $phones,
                'telegram' => $telegram,
            ];
        }

        return $out;
    }

    public function assertOwnerHasPublicContact(string $ownerIdString): void
    {
        $id = Id::fromString($ownerIdString)->getValue();
        $resolved = $this->resolveForOwnerIds([$id])[$id] ?? ['phone' => null, 'name' => null, 'phones' => [], 'telegram' => null];

        if ($resolved['phone'] === null) {
            throw new DomainException('Подтвердите телефон в профиле');
        }
        if ($resolved['name'] === null) {
            throw new DomainException('Укажите имя в профиле');
        }
    }

    private function normalizePhoneKey(string $phone): string
    {
        try {
            return PhoneNumberNormalizer::normalize($phone);
        } catch (\InvalidArgumentException) {
            return preg_replace('/\D+/', '', $phone) ?? $phone;
        }
    }
}
