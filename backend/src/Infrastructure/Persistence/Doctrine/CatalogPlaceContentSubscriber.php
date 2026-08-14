<?php

declare(strict_types=1);

namespace App\Infrastructure\Persistence\Doctrine;

use App\Application\Service\CatalogPlaceContentNormalizer;
use App\Domain\Property\Entity\City;
use App\Domain\Property\Entity\CityDistrict;
use App\Domain\Property\Entity\CityMicrodistrict;
use App\Domain\Property\Entity\ResidentialComplex;
use Doctrine\Common\EventSubscriber;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\Event\OnFlushEventArgs;
use Doctrine\ORM\Events;

/**
 * Normalizes catalog SEO text and FAQ on persist. Uses onFlush (not preUpdate) so changes are applied after
 * change-set calculation and before SQL runs; preUpdate runs too late for reliable UPDATE payloads.
 */
final class CatalogPlaceContentSubscriber implements EventSubscriber
{
    public function __construct(
        private readonly CatalogPlaceContentNormalizer $catalogPlaceContentNormalizer,
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

        foreach ($uow->getScheduledEntityInsertions() as $entity) {
            if (!$this->isCatalogPlaceEntity($entity)) {
                continue;
            }

            $this->catalogPlaceContentNormalizer->normalizeEntity($entity);
        }

        foreach ($uow->getScheduledEntityUpdates() as $entity) {
            if (!$this->isCatalogPlaceEntity($entity)) {
                continue;
            }

            $this->catalogPlaceContentNormalizer->normalizeEntity($entity);
            $uow->recomputeSingleEntityChangeSet($em->getClassMetadata($entity::class), $entity);
        }
    }

    private function isCatalogPlaceEntity(object $entity): bool
    {
        return $entity instanceof City
            || $entity instanceof CityDistrict
            || $entity instanceof CityMicrodistrict
            || $entity instanceof ResidentialComplex;
    }
}
