<?php

declare(strict_types=1);

namespace App\Presentation\Admin\Controller;

use App\Domain\Property\Entity\Street;
use EasyCorp\Bundle\EasyAdminBundle\Config\Crud;
use EasyCorp\Bundle\EasyAdminBundle\Config\Filters;
use EasyCorp\Bundle\EasyAdminBundle\Controller\AbstractCrudController;
use EasyCorp\Bundle\EasyAdminBundle\Field\AssociationField;
use EasyCorp\Bundle\EasyAdminBundle\Field\IdField;
use EasyCorp\Bundle\EasyAdminBundle\Field\TextField;

class StreetCrudController extends AbstractCrudController
{
    public static function getEntityFqcn(): string
    {
        return Street::class;
    }

    public function configureCrud(Crud $crud): Crud
    {
        return $crud
            ->setEntityLabelInSingular('Улица')
            ->setEntityLabelInPlural('Улицы')
            ->setDefaultSort(['name' => 'ASC'])
            ->setSearchFields(['name', 'slug', 'type']);
    }

    public function configureFields(string $pageName): iterable
    {
        yield IdField::new('id', 'ID')
            ->hideOnForm();

        yield TextField::new('name', 'Название');
        yield TextField::new('slug', 'Slug');
        yield TextField::new('type', 'Тип');

        yield AssociationField::new('city', 'Город');

        yield TextField::new('externalId', 'Внешний ID')
            ->hideOnIndex();
    }

    public function configureFilters(Filters $filters): Filters
    {
        return $filters
            ->add('city')
            ->add('type');
    }
}
