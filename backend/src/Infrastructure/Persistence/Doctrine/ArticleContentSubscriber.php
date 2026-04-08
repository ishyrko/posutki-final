<?php

declare(strict_types=1);

namespace App\Infrastructure\Persistence\Doctrine;

use App\Application\Service\ArticleHtmlNormalizer;
use App\Domain\Article\Entity\Article;
use Doctrine\Common\EventSubscriber;
use Doctrine\ORM\Event\PrePersistEventArgs;
use Doctrine\ORM\Event\PreUpdateEventArgs;
use Doctrine\ORM\Events;

/**
 * Normalizes article HTML on any persist (admin, API, view increments with flush).
 */
final class ArticleContentSubscriber implements EventSubscriber
{
    public function __construct(
        private readonly ArticleHtmlNormalizer $articleHtmlNormalizer,
    ) {
    }

    public function getSubscribedEvents(): array
    {
        return [
            Events::prePersist => 'prePersist',
            Events::preUpdate => 'preUpdate',
        ];
    }

    public function prePersist(PrePersistEventArgs $args): void
    {
        $this->normalizeContent($args->getObject());
    }

    public function preUpdate(PreUpdateEventArgs $args): void
    {
        $this->normalizeContent($args->getObject());
    }

    private function normalizeContent(object $entity): void
    {
        if (!$entity instanceof Article) {
            return;
        }

        $normalized = $this->articleHtmlNormalizer->normalize($entity->getContent());
        if ($normalized === $entity->getContent()) {
            return;
        }

        $entity->setContent($normalized);
    }
}
