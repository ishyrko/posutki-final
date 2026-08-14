<?php

declare(strict_types=1);

namespace App\Presentation\Admin\Controller;

use App\Application\Service\CatalogPlaceContentNormalizer;
use App\Domain\Property\Entity\City;
use App\Domain\Property\Service\CatalogApartmentCitySlugs;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\QueryBuilder;
use EasyCorp\Bundle\EasyAdminBundle\Collection\FieldCollection;
use EasyCorp\Bundle\EasyAdminBundle\Collection\FilterCollection;
use EasyCorp\Bundle\EasyAdminBundle\Config\Assets;
use EasyCorp\Bundle\EasyAdminBundle\Config\Crud;
use EasyCorp\Bundle\EasyAdminBundle\Config\Filters;
use EasyCorp\Bundle\EasyAdminBundle\Controller\AbstractCrudController;
use EasyCorp\Bundle\EasyAdminBundle\Dto\EntityDto;
use EasyCorp\Bundle\EasyAdminBundle\Dto\SearchDto;
use EasyCorp\Bundle\EasyAdminBundle\Field\AssociationField;
use EasyCorp\Bundle\EasyAdminBundle\Field\IdField;
use EasyCorp\Bundle\EasyAdminBundle\Field\NumberField;
use EasyCorp\Bundle\EasyAdminBundle\Field\TextField;

class CityCrudController extends AbstractCrudController
{
    public function __construct(
        private readonly CatalogPlaceContentNormalizer $catalogPlaceContentNormalizer,
    ) {
    }

    public static function getEntityFqcn(): string
    {
        return City::class;
    }

    public function configureCrud(Crud $crud): Crud
    {
        return $crud
            ->setEntityLabelInSingular('Город')
            ->setEntityLabelInPlural('Города')
            ->setSearchFields(['name', 'shortName', 'slug']);
    }

    public function createIndexQueryBuilder(
        SearchDto $searchDto,
        EntityDto $entityDto,
        FieldCollection $fields,
        FilterCollection $filters,
    ): QueryBuilder {
        $qb = parent::createIndexQueryBuilder($searchDto, $entityDto, $fields, $filters);
        $rootAlias = $qb->getRootAliases()[0];

        $qb->resetDQLPart('orderBy');

        $priorityExpr = CatalogApartmentCitySlugs::priorityOrderDql($rootAlias);
        $qb->addSelect(sprintf('%s AS HIDDEN catalogCityPriority', $priorityExpr));
        $qb->addSelect(sprintf('%s.name AS HIDDEN catalogCityName', $rootAlias));

        return $qb
            ->addOrderBy('catalogCityPriority', 'ASC')
            ->addOrderBy('catalogCityName', 'ASC');
    }

    public function configureAssets(Assets $assets): Assets
    {
        return CatalogContentAdminFields::configureAssets($assets);
    }

    public function persistEntity(EntityManagerInterface $entityManager, $entityInstance): void
    {
        if ($entityInstance instanceof City) {
            CatalogContentAdminFields::normalize($entityInstance, $this->catalogPlaceContentNormalizer);
        }

        parent::persistEntity($entityManager, $entityInstance);
    }

    public function updateEntity(EntityManagerInterface $entityManager, $entityInstance): void
    {
        if ($entityInstance instanceof City) {
            CatalogContentAdminFields::normalize($entityInstance, $this->catalogPlaceContentNormalizer);
        }

        parent::updateEntity($entityManager, $entityInstance);
    }

    public function configureFields(string $pageName): iterable
    {
        yield IdField::new('id', 'ID')
            ->hideOnForm();

        yield TextField::new('name', 'Название');
        yield TextField::new('slug', 'Slug');
        yield TextField::new('shortName', 'Короткое название');
        yield TextField::new('ruralCouncil', 'Сельсовет')
            ->hideOnIndex();

        yield AssociationField::new('regionDistrict', 'Район области');

        yield NumberField::new('latitude', 'Широта')
            ->setNumDecimals(7)
            ->hideOnIndex();

        yield NumberField::new('longitude', 'Долгота')
            ->setNumDecimals(7)
            ->hideOnIndex();

        yield TextField::new('externalId', 'Внешний ID')
            ->hideOnIndex();

        yield CatalogContentAdminFields::seoTextField('SEO-текст под каталогом квартир')
            ->setHelp('Показывается на первой странице каталога квартир этого города (под списком объявлений). Если пусто — блок не отображается.');
        yield CatalogContentAdminFields::faqField();
    }

    public function configureFilters(Filters $filters): Filters
    {
        return $filters
            ->add('regionDistrict');
    }
}
