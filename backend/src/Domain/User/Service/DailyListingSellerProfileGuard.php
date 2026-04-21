<?php

declare(strict_types=1);

namespace App\Domain\User\Service;

use App\Domain\Property\Enum\DealType;
use App\Domain\Property\Enum\SellerType;
use App\Domain\Shared\Exception\DomainException;
use App\Domain\Shared\ValueObject\Id;
use App\Domain\User\Repository\UserBusinessProfileRepositoryInterface;
use App\Domain\User\Repository\UserIndividualProfileRepositoryInterface;

final class DailyListingSellerProfileGuard implements DailyListingSellerProfileGuardInterface
{
    public function __construct(
        private readonly UserIndividualProfileRepositoryInterface $individualProfileRepository,
        private readonly UserBusinessProfileRepositoryInterface $businessProfileRepository,
    ) {
    }

    public function assertEligible(string $ownerId, string $dealType, ?string $sellerType): void
    {
        if ($dealType !== DealType::Daily->value) {
            return;
        }

        if ($sellerType === null || trim($sellerType) === '') {
            throw new DomainException('Укажите тип продавца для посуточного объявления: физическое лицо или организация');
        }

        $seller = SellerType::tryFrom($sellerType);
        if ($seller === null) {
            throw new DomainException('Недопустимый тип продавца');
        }

        $userId = Id::fromString($ownerId);

        match ($seller) {
            SellerType::Individual => $this->assertIndividualComplete($userId),
            SellerType::Business => $this->assertBusinessComplete($userId),
        };
    }

    private function assertIndividualComplete(Id $userId): void
    {
        $profile = $this->individualProfileRepository->findByUserId($userId);
        if ($profile === null) {
            throw new DomainException(
                'Для посуточных объявлений от физического лица заполните в кабинете блок «Данные физлица (для посуточных)»'
            );
        }

        if (trim($profile->getLastName()) === '' || trim($profile->getFirstName()) === '' || trim($profile->getUnp()) === '') {
            throw new DomainException(
                'Для посуточных объявлений от физического лица заполните фамилию, имя и УНП в кабинете'
            );
        }
    }

    private function assertBusinessComplete(Id $userId): void
    {
        $profile = $this->businessProfileRepository->findByUserId($userId);
        if ($profile === null) {
            throw new DomainException(
                'Для посуточных объявлений от организации заполните в кабинете блок «Данные организации (для посуточных)»'
            );
        }

        if (
            trim($profile->getOrganizationName()) === ''
            || trim($profile->getContactName()) === ''
            || trim($profile->getUnp()) === ''
        ) {
            throw new DomainException(
                'Для посуточных объявлений от организации заполните наименование, контактное лицо и УНП в кабинете'
            );
        }
    }
}
