<?php

declare(strict_types=1);

namespace App\Presentation\Admin\Controller;

use App\Domain\Property\Entity\City;
use EasyCorp\Bundle\EasyAdminBundle\Config\Crud;
use EasyCorp\Bundle\EasyAdminBundle\Config\Filters;
use EasyCorp\Bundle\EasyAdminBundle\Controller\AbstractCrudController;
use EasyCorp\Bundle\EasyAdminBundle\Field\AssociationField;
use EasyCorp\Bundle\EasyAdminBundle\Field\IdField;
use EasyCorp\Bundle\EasyAdminBundle\Field\NumberField;
use EasyCorp\Bundle\EasyAdminBundle\Field\TextField;

class CityCrudController extends AbstractCrudController
{
    public static function getEntityFqcn(): string
    {
        return City::class;
    }

    public function configureCrud(Crud $crud): Crud
    {
        return $crud
            ->setEntityLabelInSingular('Город')
            ->setEntityLabelInPlural('Города')
            ->setDefaultSort(['name' => 'ASC'])
            ->setSearchFields(['name', 'shortName', 'slug']);
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
    }

    public function configureFilters(Filters $filters): Filters
    {
        return $filters
            ->add('regionDistrict');
    }
}
