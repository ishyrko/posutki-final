<?php

declare(strict_types=1);

namespace App\Infrastructure\Persistence\Doctrine\Repository;

use App\Domain\Property\Entity\Property;
use App\Domain\Review\Entity\Review;
use App\Domain\Review\Repository\ReviewRepositoryInterface;
use App\Domain\Review\ValueObject\ReviewStatus;
use App\Domain\Shared\ValueObject\Id;
use App\Domain\User\Entity\User;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<Review>
 */
class ReviewRepository extends ServiceEntityRepository implements ReviewRepositoryInterface
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Review::class);
    }

    public function save(Review $review): void
    {
        $this->getEntityManager()->persist($review);
        $this->getEntityManager()->flush();
    }

    public function delete(Review $review): void
    {
        $this->getEntityManager()->remove($review);
        $this->getEntityManager()->flush();
    }

    public function findById(Id $id): ?Review
    {
        return $this->find($id->getValue());
    }

    public function findByAuthorAndProperty(Id $authorId, Id $propertyId): ?Review
    {
        return $this->createQueryBuilder('r')
            ->where('r.author = :author')
            ->andWhere('r.property = :property')
            ->setParameter('author', $this->getEntityManager()->getReference(User::class, $authorId->getValue()))
            ->setParameter('property', $this->getEntityManager()->getReference(Property::class, $propertyId->getValue()))
            ->getQuery()
            ->getOneOrNullResult();
    }

    public function findApprovedByPropertyId(Id $propertyId): array
    {
        $property = $this->getEntityManager()->getReference(Property::class, $propertyId->getValue());

        return $this->createQueryBuilder('r')
            ->innerJoin('r.author', 'author')->addSelect('author')
            ->where('r.property = :property')
            ->andWhere('r.status = :status')
            ->setParameter('property', $property)
            ->setParameter('status', ReviewStatus::Approved)
            ->orderBy('r.createdAt', 'DESC')
            ->getQuery()
            ->getResult();
    }

    public function getAggregateByPropertyId(Id $propertyId): array
    {
        $property = $this->getEntityManager()->getReference(Property::class, $propertyId->getValue());

        $row = $this->createQueryBuilder('r')
            ->select('AVG(r.rating) AS avgRating', 'COUNT(r.id) AS reviewCount')
            ->where('r.property = :property')
            ->andWhere('r.status = :status')
            ->setParameter('property', $property)
            ->setParameter('status', ReviewStatus::Approved)
            ->getQuery()
            ->getSingleResult();

        $avg = $row['avgRating'];
        $count = (int) $row['reviewCount'];

        return [
            'avg' => $avg !== null ? round((float) $avg, 2) : null,
            'count' => $count,
        ];
    }
}
