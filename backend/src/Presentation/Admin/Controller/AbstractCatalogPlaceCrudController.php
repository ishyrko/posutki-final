<?php

declare(strict_types=1);

namespace App\Presentation\Admin\Controller;

use App\Application\Service\CatalogPlaceContentNormalizer;
use App\Domain\Property\Entity\CityDistrict;
use App\Domain\Property\Entity\CityMicrodistrict;
use App\Domain\Property\Entity\ResidentialComplex;
use Doctrine\ORM\EntityManagerInterface;
use EasyCorp\Bundle\EasyAdminBundle\Config\Assets;
use EasyCorp\Bundle\EasyAdminBundle\Controller\AbstractCrudController;
use EasyCorp\Bundle\EasyAdminBundle\Field\IdField;
use EasyCorp\Bundle\EasyAdminBundle\Field\IntegerField;
use EasyCorp\Bundle\EasyAdminBundle\Field\TextField;

abstract class AbstractCatalogPlaceCrudController extends AbstractCrudController
{
    public function __construct(
        protected readonly CatalogPlaceContentNormalizer $catalogPlaceContentNormalizer,
    ) {
    }

    public function configureAssets(Assets $assets): Assets
    {
        return CatalogContentAdminFields::configureAssets($assets);
    }

    public function persistEntity(EntityManagerInterface $entityManager, $entityInstance): void
    {
        $this->normalizeCatalogContent($entityInstance);
        parent::persistEntity($entityManager, $entityInstance);
    }

    public function updateEntity(EntityManagerInterface $entityManager, $entityInstance): void
    {
        $this->normalizeCatalogContent($entityInstance);
        parent::updateEntity($entityManager, $entityInstance);
    }

    /**
     * @return iterable<int, mixed>
     */
    protected function catalogPlaceFields(): iterable
    {
        yield IdField::new('id', 'ID')->hideOnForm();
        yield IntegerField::new('cityId', 'ID города');
        yield TextField::new('officialName', 'Официальное название');
        yield TextField::new('name', 'Название');
        yield TextField::new('namePrepositional', 'Предложный падеж');
        yield TextField::new('slug', 'Slug');
        yield CatalogContentAdminFields::visibilityField();
        yield CatalogContentAdminFields::seoTextField();
        yield CatalogContentAdminFields::faqField();
    }

    private function normalizeCatalogContent(object $entity): void
    {
        if (!$entity instanceof CityDistrict && !$entity instanceof CityMicrodistrict && !$entity instanceof ResidentialComplex) {
            return;
        }

        CatalogContentAdminFields::normalize($entity, $this->catalogPlaceContentNormalizer);
    }
}
