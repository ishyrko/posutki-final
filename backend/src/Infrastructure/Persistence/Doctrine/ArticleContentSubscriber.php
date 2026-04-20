<?php

declare(strict_types=1);

namespace App\Infrastructure\Persistence\Doctrine;

use App\Application\Service\ArticleContentPersistNormalizer;
use App\Domain\Article\Entity\Article;
use Doctrine\Common\EventSubscriber;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\Event\OnFlushEventArgs;
use Doctrine\ORM\Events;

/**
 * Normalizes article HTML on persist. Uses onFlush (not preUpdate) so changes are applied after
 * change-set calculation and before SQL runs; preUpdate runs too late for reliable UPDATE payloads.
 */
final class ArticleContentSubscriber implements EventSubscriber
{
    public function __construct(
        private readonly ArticleContentPersistNormalizer $articleContentPersistNormalizer,
    ) {
    }

    public function getSubscribedEvents(): array
    {
        return [
            Events::onFlush => 'onFlush',
        ];
    }

    public function onFlush(OnFlushEventArgs $args): void
    {
        $em = $args->getObjectManager();
        if (!$em instanceof EntityManagerInterface) {
            return;
        }

        $uow = $em->getUnitOfWork();
        $articleMeta = $em->getClassMetadata(Article::class);

        foreach ($uow->getScheduledEntityInsertions() as $entity) {
            if (!$entity instanceof Article) {
                continue;
            }

            $this->articleContentPersistNormalizer->normalize($entity);
        }

        foreach ($uow->getScheduledEntityUpdates() as $entity) {
            if (!$entity instanceof Article) {
                continue;
            }

            $this->articleContentPersistNormalizer->normalize($entity);
            $uow->recomputeSingleEntityChangeSet($articleMeta, $entity);
        }
    }
}
