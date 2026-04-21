<?php

declare(strict_types=1);

namespace App\Infrastructure\Persistence\Doctrine\Repository;

use App\Domain\Shared\ValueObject\Id;
use App\Domain\User\Entity\User;
use App\Domain\User\Entity\UserIndividualProfile;
use App\Domain\User\Repository\UserIndividualProfileRepositoryInterface;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

class UserIndividualProfileRepository extends ServiceEntityRepository implements UserIndividualProfileRepositoryInterface
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, UserIndividualProfile::class);
    }

    public function findByUserId(Id $userId): ?UserIndividualProfile
    {
        $userRef = $this->getEntityManager()->getReference(User::class, $userId->getValue());

        return $this->find($userRef);
    }

    public function save(UserIndividualProfile $profile): void
    {
        $this->getEntityManager()->persist($profile);
        $this->getEntityManager()->flush();
    }
}
