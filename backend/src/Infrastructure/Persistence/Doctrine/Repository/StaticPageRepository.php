<?php

declare(strict_types=1);

namespace App\Infrastructure\Persistence\Doctrine\Repository;

use App\Domain\Shared\ValueObject\Id;
use App\Domain\Shared\ValueObject\Slug;
use App\Domain\StaticPage\Entity\StaticPage;
use App\Domain\StaticPage\Repository\StaticPageRepositoryInterface;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

class StaticPageRepository extends ServiceEntityRepository implements StaticPageRepositoryInterface
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, StaticPage::class);
    }

    public function save(StaticPage $page): void
    {
        $this->getEntityManager()->persist($page);
        $this->getEntityManager()->flush();
    }

    public function findById(Id $id): ?StaticPage
    {
        return $this->find($id->getValue());
    }

    public function findBySlug(Slug $slug): ?StaticPage
    {
        return $this->createQueryBuilder('p')
            ->where('p.slug = :slug')
            ->setParameter('slug', $slug->getValue())
            ->getQuery()
            ->getOneOrNullResult();
    }

    public function delete(StaticPage $page): void
    {
        $this->getEntityManager()->remove($page);
        $this->getEntityManager()->flush();
    }
}
