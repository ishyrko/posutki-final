<?php

declare(strict_types=1);

namespace App\Infrastructure\Persistence\Doctrine\Repository;

use App\Domain\Shared\ValueObject\Id;
use App\Domain\User\Entity\User;
use App\Domain\User\Entity\UserBusinessProfile;
use App\Domain\User\Repository\UserBusinessProfileRepositoryInterface;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

class UserBusinessProfileRepository extends ServiceEntityRepository implements UserBusinessProfileRepositoryInterface
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, UserBusinessProfile::class);
    }

    public function findByUserId(Id $userId): ?UserBusinessProfile
    {
        $userRef = $this->getEntityManager()->getReference(User::class, $userId->getValue());

        return $this->find($userRef);
    }

    public function save(UserBusinessProfile $profile): void
    {
        $this->getEntityManager()->persist($profile);
        $this->getEntityManager()->flush();
    }
}
