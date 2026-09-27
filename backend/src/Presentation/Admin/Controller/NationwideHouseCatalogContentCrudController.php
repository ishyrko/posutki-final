<?php

declare(strict_types=1);

namespace App\Presentation\Admin\Controller;

use App\Application\Service\CatalogPlaceContentNormalizer;
use App\Domain\Property\Entity\NationwideHouseCatalogContent;
use Doctrine\ORM\EntityManagerInterface;
use EasyCorp\Bundle\EasyAdminBundle\Config\Action;
use EasyCorp\Bundle\EasyAdminBundle\Config\Actions;
use EasyCorp\Bundle\EasyAdminBundle\Config\Crud;
use EasyCorp\Bundle\EasyAdminBundle\Controller\AbstractCrudController;
use EasyCorp\Bundle\EasyAdminBundle\Field\IdField;
use EasyCorp\Bundle\EasyAdminBundle\Field\TextField;

class NationwideHouseCatalogContentCrudController extends AbstractCrudController
{
    public function __construct(
        private readonly CatalogPlaceContentNormalizer $catalogPlaceContentNormalizer,
    ) {
    }

    public static function getEntityFqcn(): string
    {
        return NationwideHouseCatalogContent::class;
    }

    public function configureCrud(Crud $crud): Crud
    {
        return $crud
            ->setEntityLabelInSingular('SEO усадеб в Беларуси')
            ->setEntityLabelInPlural('SEO усадеб в Беларуси')
            ->setPageTitle(Crud::PAGE_INDEX, 'SEO усадеб в Беларуси')
            ->setSearchFields(['id']);
    }

    public function configureActions(Actions $actions): Actions
    {
        return $actions->disable(Action::NEW, Action::DELETE);
    }

    public function configureAssets(\EasyCorp\Bundle\EasyAdminBundle\Config\Assets $assets): \EasyCorp\Bundle\EasyAdminBundle\Config\Assets
    {
        return CatalogContentAdminFields::configureAssets($assets);
    }

    public function updateEntity(EntityManagerInterface $entityManager, $entityInstance): void
    {
        $this->normalizeEntity($entityInstance);
        parent::updateEntity($entityManager, $entityInstance);
    }

    public function configureFields(string $pageName): iterable
    {
        yield IdField::new('id', 'ID')->hideOnForm();
        yield TextField::new('scopeLabel', 'Раздел')->hideOnForm();
        yield CatalogContentAdminFields::visibilityField();
        yield CatalogContentAdminFields::seoTextField('SEO-текст под каталогом усадеб в Беларуси')
            ->setHelp('Первая страница /usadby-v-belarusi/. Если видимость выключена, блок на сайте не показывается.');
        yield CatalogContentAdminFields::faqField();
    }

    private function normalizeEntity(object $entity): void
    {
        if (!$entity instanceof NationwideHouseCatalogContent) {
            return;
        }

        CatalogContentAdminFields::normalize($entity, $this->catalogPlaceContentNormalizer);
        CatalogContentAdminFields::refreshFaqReference($entity);
    }
}
