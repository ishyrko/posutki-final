<?php

declare(strict_types=1);

namespace App\Infrastructure\Persistence\Doctrine\Repository;

use App\Domain\Shared\ValueObject\Id;
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
        return $this->createQueryBuilder('profile')
            ->andWhere('IDENTITY(profile.user) = :userId')
            ->setParameter('userId', $userId->getValue())
            ->getQuery()
            ->getOneOrNullResult();
    }

    public function save(UserIndividualProfile $profile): void
    {
        $this->getEntityManager()->persist($profile);
        $this->getEntityManager()->flush();
    }

    public function deleteByUserId(Id $userId): void
    {
        $profile = $this->findByUserId($userId);
        if ($profile === null) {
            return;
        }

        $em = $this->getEntityManager();
        $em->remove($profile);
        $em->flush();
    }
}
