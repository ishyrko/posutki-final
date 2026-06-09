<?php

declare(strict_types=1);

namespace App\Infrastructure\Persistence\Doctrine\Repository;

use App\Domain\Shared\ValueObject\Id;
use App\Domain\User\Entity\RefreshToken;
use App\Domain\User\Repository\RefreshTokenRepositoryInterface;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

class RefreshTokenRepository extends ServiceEntityRepository implements RefreshTokenRepositoryInterface
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, RefreshToken::class);
    }

    public function save(RefreshToken $refreshToken): void
    {
        $this->getEntityManager()->persist($refreshToken);
        $this->getEntityManager()->flush();
    }

    public function findByTokenHash(string $tokenHash): ?RefreshToken
    {
        return $this->createQueryBuilder('rt')
            ->where('rt.tokenHash = :hash')
            ->setParameter('hash', $tokenHash)
            ->getQuery()
            ->getOneOrNullResult();
    }

    public function delete(RefreshToken $refreshToken): void
    {
        $this->getEntityManager()->remove($refreshToken);
        $this->getEntityManager()->flush();
    }

    public function deleteAllForUser(Id $userId): void
    {
        $this->createQueryBuilder('rt')
            ->delete()
            ->where('rt.userId = :userId')
            ->setParameter('userId', $userId)
            ->getQuery()
            ->execute();
    }
}
