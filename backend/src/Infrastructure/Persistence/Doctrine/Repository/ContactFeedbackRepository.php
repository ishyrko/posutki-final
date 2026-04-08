<?php

declare(strict_types=1);

namespace App\Infrastructure\Persistence\Doctrine\Repository;

use App\Domain\ContactFeedback\Entity\ContactFeedback;
use App\Domain\ContactFeedback\Repository\ContactFeedbackRepositoryInterface;
use App\Domain\Shared\ValueObject\Id;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

class ContactFeedbackRepository extends ServiceEntityRepository implements ContactFeedbackRepositoryInterface
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, ContactFeedback::class);
    }

    public function save(ContactFeedback $feedback): void
    {
        $this->getEntityManager()->persist($feedback);
        $this->getEntityManager()->flush();
    }

    public function findById(string $id): ?ContactFeedback
    {
        return $this->find(Id::fromString($id)->getValue());
    }
}
