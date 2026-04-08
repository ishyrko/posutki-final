<?php

declare(strict_types=1);

namespace App\Infrastructure\Persistence\Doctrine\Repository;

use App\Domain\User\Entity\UserPhone;
use App\Domain\User\Repository\UserPhoneRepositoryInterface;
use App\Domain\Shared\ValueObject\Id;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

class UserPhoneRepository extends ServiceEntityRepository implements UserPhoneRepositoryInterface
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, UserPhone::class);
    }

    public function save(UserPhone $userPhone): void
    {
        $this->getEntityManager()->persist($userPhone);
        $this->getEntityManager()->flush();
    }

    public function delete(UserPhone $userPhone): void
    {
        $this->getEntityManager()->remove($userPhone);
        $this->getEntityManager()->flush();
    }

    public function findById(Id $id): ?UserPhone
    {
        return $this->find($id->getValue());
    }

    /** @return UserPhone[] */
    public function findByUserId(Id $userId): array
    {
        return $this->createQueryBuilder('up')
            ->where('up.userId = :userId')
            ->setParameter('userId', $userId->getValue())
            ->orderBy('up.createdAt', 'DESC')
            ->getQuery()
            ->getResult();
    }

    public function findByUserIdAndPhone(Id $userId, string $phone): ?UserPhone
    {
        return $this->createQueryBuilder('up')
            ->where('up.userId = :userId')
            ->andWhere('up.phone = :phone')
            ->setParameter('userId', $userId->getValue())
            ->setParameter('phone', $phone)
            ->getQuery()
            ->getOneOrNullResult();
    }

    /** @return UserPhone[] */
    public function findVerifiedByUserId(Id $userId): array
    {
        return $this->createQueryBuilder('up')
            ->where('up.userId = :userId')
            ->andWhere('up.isVerified = :verified')
            ->setParameter('userId', $userId->getValue())
            ->setParameter('verified', true)
            ->orderBy('up.createdAt', 'DESC')
            ->getQuery()
            ->getResult();
    }
}
