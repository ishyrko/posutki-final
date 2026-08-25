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

    public function findActiveByAuthorAndProperty(Id $authorId, Id $propertyId): ?Review
    {
        return $this->createQueryBuilder('r')
            ->where('r.author = :author')
            ->andWhere('r.property = :property')
            ->andWhere('r.status != :deleted')
            ->setParameter('author', $this->getEntityManager()->getReference(User::class, $authorId->getValue()))
            ->setParameter('property', $this->getEntityManager()->getReference(Property::class, $propertyId->getValue()))
            ->setParameter('deleted', ReviewStatus::Deleted)
            ->orderBy('r.createdAt', 'DESC')
            ->setMaxResults(1)
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

    public function findApprovedByOwnerId(Id $ownerId): array
    {
        return $this->createQueryBuilder('r')
            ->innerJoin('r.property', 'property')->addSelect('property')
            ->innerJoin('r.author', 'author')->addSelect('author')
            ->where('property.ownerId = :ownerId')
            ->andWhere('r.status = :status')
            ->setParameter('ownerId', $ownerId)
            ->setParameter('status', ReviewStatus::Approved)
            ->orderBy('r.createdAt', 'DESC')
            ->getQuery()
            ->getResult();
    }

    public function findApprovedByPropertyIdForOwner(Id $propertyId, Id $ownerId): array
    {
        $property = $this->getEntityManager()->getReference(Property::class, $propertyId->getValue());

        return $this->createQueryBuilder('r')
            ->innerJoin('r.property', 'property')->addSelect('property')
            ->innerJoin('r.author', 'author')->addSelect('author')
            ->where('r.property = :property')
            ->andWhere('property.ownerId = :ownerId')
            ->andWhere('r.status = :status')
            ->setParameter('property', $property)
            ->setParameter('ownerId', $ownerId)
            ->setParameter('status', ReviewStatus::Approved)
            ->orderBy('r.createdAt', 'DESC')
            ->getQuery()
            ->getResult();
    }

    public function countUnviewedGroupedByPropertyForOwner(Id $ownerId): array
    {
        $rows = $this->createQueryBuilder('r')
            ->select('IDENTITY(r.property) AS propertyId', 'COUNT(r.id) AS cnt')
            ->innerJoin('r.property', 'property')
            ->where('property.ownerId = :ownerId')
            ->andWhere('r.status = :status')
            ->andWhere('r.ownerSeenAt IS NULL')
            ->setParameter('ownerId', $ownerId)
            ->setParameter('status', ReviewStatus::Approved)
            ->groupBy('r.property')
            ->getQuery()
            ->getArrayResult();

        $result = [];
        foreach ($rows as $row) {
            $result[(int) $row['propertyId']] = (int) $row['cnt'];
        }

        return $result;
    }

    public function markSeenForPropertyOwner(Id $propertyId, Id $ownerId): void
    {
        $reviews = $this->findApprovedByPropertyIdForOwner($propertyId, $ownerId);
        $changed = false;
        foreach ($reviews as $review) {
            if ($review->getOwnerSeenAt() === null) {
                $review->markSeenByOwner();
                $changed = true;
            }
        }

        if ($changed) {
            $this->getEntityManager()->flush();
        }
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
