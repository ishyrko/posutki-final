<?php

declare(strict_types=1);

namespace App\Infrastructure\Persistence\Doctrine\Repository;

use App\Domain\Article\Entity\Article;
use App\Domain\Article\Repository\ArticleRepositoryInterface;
use App\Domain\Shared\ValueObject\{Id, Slug};
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

class ArticleRepository extends ServiceEntityRepository implements ArticleRepositoryInterface
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Article::class);
    }

    public function save(Article $article): void
    {
        $this->getEntityManager()->persist($article);
        $this->getEntityManager()->flush();
    }

    public function findById(Id $id): ?Article
    {
        return $this->find($id->getValue());
    }

    public function findBySlug(Slug $slug): ?Article
    {
        return $this->createQueryBuilder('a')
            ->where('a.slug = :slug')
            ->andWhere('a.status = :status')
            ->setParameter('slug', $slug->getValue())
            ->setParameter('status', 'published')
            ->getQuery()
            ->getOneOrNullResult();
    }

    public function findPublished(int $page = 1, int $limit = 20): array
    {
        return $this->createQueryBuilder('a')
            ->where('a.status = :status')
            ->setParameter('status', 'published')
            ->orderBy('a.publishedAt', 'DESC')
            ->setFirstResult(($page - 1) * $limit)
            ->setMaxResults($limit)
            ->getQuery()
            ->getResult();
    }

    public function search(array $filters, int $page, int $limit): array
    {
        $qb = $this->createQueryBuilder('a');

        // Filter by status
        if (isset($filters['status'])) {
            $qb->andWhere('a.status = :status')
                ->setParameter('status', $filters['status']);
        }

        if (isset($filters['categoryId'])) {
            $qb->andWhere('a.category = :categoryId')
                ->setParameter('categoryId', $filters['categoryId']);
        }

        if (isset($filters['categorySlug'])) {
            $qb->join('a.category', 'c')
                ->andWhere('c.slug = :categorySlug')
                ->setParameter('categorySlug', $filters['categorySlug']);
        }

        // Filter by tag
        if (isset($filters['tag'])) {
            $qb->andWhere('JSON_CONTAINS(a.tags, :tag) = 1')
                ->setParameter('tag', json_encode($filters['tag']));
        }

        // Filter by author
        if (isset($filters['authorId'])) {
            $qb->andWhere('a.authorId = :authorId')
                ->setParameter('authorId', $filters['authorId']);
        }

        // Order by published date (newest first)
        $qb->orderBy('a.publishedAt', 'DESC')
            ->addOrderBy('a.createdAt', 'DESC');

        // Pagination
        $offset = ($page - 1) * $limit;
        $qb->setFirstResult($offset)
            ->setMaxResults($limit);

        return $qb->getQuery()->getResult();
    }

    public function delete(Article $article): void
    {
        $this->getEntityManager()->remove($article);
        $this->getEntityManager()->flush();
    }
}