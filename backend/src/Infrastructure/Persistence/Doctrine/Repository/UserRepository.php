<?php

declare(strict_types=1);

namespace App\Infrastructure\Persistence\Doctrine\Repository;

use App\Domain\User\Entity\User;
use App\Domain\User\Repository\UserRepositoryInterface;
use App\Domain\User\ValueObject\Email;
use App\Domain\Shared\ValueObject\Id;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

class UserRepository extends ServiceEntityRepository implements UserRepositoryInterface
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, User::class);
    }

    public function save(User $user): void
    {
        $this->getEntityManager()->persist($user);
        $this->getEntityManager()->flush();
    }

    public function findById(Id $id): ?User
    {
        return $this->find($id->getValue());
    }

    public function findByIds(array $ids): array
    {
        $ids = array_values(array_unique(array_filter(
            $ids,
            static fn(mixed $id): bool => is_int($id) && $id > 0,
        )));
        if ($ids === []) {
            return [];
        }

        $users = $this->createQueryBuilder('u')
            ->where('u.id IN (:ids)')
            ->setParameter('ids', $ids)
            ->getQuery()
            ->getResult();

        $map = [];
        foreach ($users as $user) {
            if ($user instanceof User) {
                $map[$user->getId()->getValue()] = $user;
            }
        }

        return $map;
    }

    public function findByEmail(Email $email): ?User
    {
        return $this->createQueryBuilder('u')
            ->where('u.email = :email')
            ->setParameter('email', $email->getValue())
            ->getQuery()
            ->getOneOrNullResult();
    }

    public function findByPendingEmail(Email $email): ?User
    {
        return $this->createQueryBuilder('u')
            ->where('u.pendingEmail = :pendingEmail')
            ->setParameter('pendingEmail', $email->getValue())
            ->getQuery()
            ->getOneOrNullResult();
    }

    public function findByPhone(string $phone): ?User
    {
        return $this->createQueryBuilder('u')
            ->where('u.phone = :phone')
            ->setParameter('phone', $phone)
            ->getQuery()
            ->getOneOrNullResult();
    }

    public function findVerifiedByPhone(string $phone): ?User
    {
        return $this->createQueryBuilder('u')
            ->where('u.phone = :phone')
            ->andWhere('u.isPhoneVerified = :verified')
            ->setParameter('phone', $phone)
            ->setParameter('verified', true)
            ->getQuery()
            ->getOneOrNullResult();
    }

    public function delete(User $user): void
    {
        $this->getEntityManager()->remove($user);
        $this->getEntityManager()->flush();
    }
}