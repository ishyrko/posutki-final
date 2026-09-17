<?php

declare(strict_types=1);

namespace App\Infrastructure\Persistence\Doctrine\Repository;

use App\Domain\Shared\ValueObject\Id;
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
        return $this->createQueryBuilder('profile')
            ->andWhere('IDENTITY(profile.user) = :userId')
            ->setParameter('userId', $userId->getValue())
            ->getQuery()
            ->getOneOrNullResult();
    }

    public function save(UserBusinessProfile $profile): void
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
