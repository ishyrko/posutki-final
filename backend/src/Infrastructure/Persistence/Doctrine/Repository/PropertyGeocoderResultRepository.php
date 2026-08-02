<?php

declare(strict_types=1);

namespace App\Infrastructure\Persistence\Doctrine\Repository;

use App\Domain\Property\Entity\PropertyGeocoderResult;
use App\Domain\Property\Repository\PropertyGeocoderResultRepositoryInterface;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

class PropertyGeocoderResultRepository extends ServiceEntityRepository implements PropertyGeocoderResultRepositoryInterface
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, PropertyGeocoderResult::class);
    }

    public function findByPropertyId(int $propertyId): ?PropertyGeocoderResult
    {
        return $this->findOneBy(['propertyId' => $propertyId]);
    }

    public function save(PropertyGeocoderResult $result): void
    {
        $this->getEntityManager()->persist($result);
        $this->getEntityManager()->flush();
    }
}
