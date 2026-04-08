<?php

declare(strict_types=1);

namespace App\Infrastructure\Persistence\Doctrine\Repository;

use App\Domain\User\Entity\PhoneAuthCode;
use App\Domain\User\Repository\PhoneAuthCodeRepositoryInterface;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

class PhoneAuthCodeRepository extends ServiceEntityRepository implements PhoneAuthCodeRepositoryInterface
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, PhoneAuthCode::class);
    }

    public function save(PhoneAuthCode $phoneAuthCode): void
    {
        $this->getEntityManager()->persist($phoneAuthCode);
        $this->getEntityManager()->flush();
    }

    public function findByPhone(string $phone): ?PhoneAuthCode
    {
        return $this->createQueryBuilder('pac')
            ->where('pac.phone = :phone')
            ->setParameter('phone', $phone)
            ->getQuery()
            ->getOneOrNullResult();
    }

    public function delete(PhoneAuthCode $phoneAuthCode): void
    {
        $this->getEntityManager()->remove($phoneAuthCode);
        $this->getEntityManager()->flush();
    }
}
