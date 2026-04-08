<?php

declare(strict_types=1);

namespace App\Presentation\Admin\Controller;

use App\Domain\Property\Entity\MetroStation;
use EasyCorp\Bundle\EasyAdminBundle\Config\Action;
use EasyCorp\Bundle\EasyAdminBundle\Config\Actions;
use EasyCorp\Bundle\EasyAdminBundle\Config\Crud;
use EasyCorp\Bundle\EasyAdminBundle\Config\Filters;
use EasyCorp\Bundle\EasyAdminBundle\Controller\AbstractCrudController;
use EasyCorp\Bundle\EasyAdminBundle\Field\ChoiceField;
use EasyCorp\Bundle\EasyAdminBundle\Field\IdField;
use EasyCorp\Bundle\EasyAdminBundle\Field\IntegerField;
use EasyCorp\Bundle\EasyAdminBundle\Field\NumberField;
use EasyCorp\Bundle\EasyAdminBundle\Field\TextField;
use EasyCorp\Bundle\EasyAdminBundle\Filter\ChoiceFilter;

final class MetroStationCrudController extends AbstractCrudController
{
    public static function getEntityFqcn(): string
    {
        return MetroStation::class;
    }

    public function configureCrud(Crud $crud): Crud
    {
        return $crud
            ->setEntityLabelInSingular('Станция метро')
            ->setEntityLabelInPlural('Метро')
            ->setDefaultSort(['line' => 'ASC', 'sortOrder' => 'ASC'])
            ->setSearchFields(['name', 'slug']);
    }

    public function configureActions(Actions $actions): Actions
    {
        return $actions
            ->disable(Action::NEW, Action::DELETE);
    }

    public function configureFields(string $pageName): iterable
    {
        yield IdField::new('id', 'ID')
            ->hideOnForm();

        yield IntegerField::new('cityId', 'ID города');

        yield ChoiceField::new('line', 'Линия')
            ->setChoices([
                'Московская (1)' => 1,
                'Автозаводская (2)' => 2,
                'Зеленолужская (3)' => 3,
            ]);

        yield IntegerField::new('sortOrder', 'Порядок');

        yield TextField::new('name', 'Название');
        yield TextField::new('slug', 'Slug');

        yield NumberField::new('latitude', 'Широта')
            ->setNumDecimals(6);

        yield NumberField::new('longitude', 'Долгота')
            ->setNumDecimals(6);
    }

    public function configureFilters(Filters $filters): Filters
    {
        return $filters
            ->add(ChoiceFilter::new('line')->setChoices([
                'Московская' => 1,
                'Автозаводская' => 2,
                'Зеленолужская' => 3,
            ]))
            ->add('cityId');
    }
}
