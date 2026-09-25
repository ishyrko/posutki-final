<?php

declare(strict_types=1);

namespace App\Presentation\Admin\Controller;

use App\Application\Service\CatalogPlaceContentNormalizer;
use App\Domain\Property\Entity\RegionCatalogContent;
use App\Domain\Property\Repository\RegionRepositoryInterface;
use Doctrine\ORM\EntityManagerInterface;
use EasyCorp\Bundle\EasyAdminBundle\Config\Crud;
use EasyCorp\Bundle\EasyAdminBundle\Controller\AbstractCrudController;
use EasyCorp\Bundle\EasyAdminBundle\Field\IdField;
use EasyCorp\Bundle\EasyAdminBundle\Field\IntegerField;

class RegionCatalogContentCrudController extends AbstractCrudController
{
    public function __construct(
        private readonly CatalogPlaceContentNormalizer $catalogPlaceContentNormalizer,
        private readonly RegionRepositoryInterface $regionRepository,
    ) {
    }

    public static function getEntityFqcn(): string
    {
        return RegionCatalogContent::class;
    }

    public function configureCrud(Crud $crud): Crud
    {
        return $crud
            ->setEntityLabelInSingular('SEO по областям')
            ->setEntityLabelInPlural('SEO по областям')
            ->setSearchFields(['regionId']);
    }

    public function configureAssets(\EasyCorp\Bundle\EasyAdminBundle\Config\Assets $assets): \EasyCorp\Bundle\EasyAdminBundle\Config\Assets
    {
        return CatalogContentAdminFields::configureAssets($assets);
    }

    public function persistEntity(EntityManagerInterface $entityManager, $entityInstance): void
    {
        $this->normalizeEntity($entityInstance);
        parent::persistEntity($entityManager, $entityInstance);
    }

    public function updateEntity(EntityManagerInterface $entityManager, $entityInstance): void
    {
        $this->normalizeEntity($entityInstance);
        parent::updateEntity($entityManager, $entityInstance);
    }

    public function configureFields(string $pageName): iterable
    {
        yield IdField::new('id', 'ID')->hideOnForm();

        yield IntegerField::new('regionId', 'Область')
            ->setFormTypeOption('attr', ['readonly' => true])
            ->formatValue(fn (?int $regionId): string => $this->formatRegionLabel($regionId));

        yield CatalogContentAdminFields::visibilityField();
        yield CatalogContentAdminFields::seoTextField('SEO-текст под каталогом по областям');
        yield CatalogContentAdminFields::faqField();
    }

    private function normalizeEntity(object $entity): void
    {
        if (!$entity instanceof RegionCatalogContent) {
            return;
        }

        CatalogContentAdminFields::normalize($entity, $this->catalogPlaceContentNormalizer);
        CatalogContentAdminFields::refreshFaqReference($entity);
    }

    private function formatRegionLabel(?int $regionId): string
    {
        if ($regionId === null) {
            return '—';
        }

        $region = $this->regionRepository->findById($regionId);
        if ($region === null) {
            return (string) $regionId;
        }

        return sprintf('%s (%s)', $region->getName(), $region->getSlug());
    }
}
