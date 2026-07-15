<?php

declare(strict_types=1);

namespace App\Presentation\Admin\Controller;

use App\Domain\Property\Entity\PropertyPlacementStandardPrice;
use App\Domain\Property\Repository\CityRepositoryInterface;
use EasyCorp\Bundle\EasyAdminBundle\Config\Crud;
use EasyCorp\Bundle\EasyAdminBundle\Config\Filters;
use EasyCorp\Bundle\EasyAdminBundle\Controller\AbstractCrudController;
use EasyCorp\Bundle\EasyAdminBundle\Field\BooleanField;
use EasyCorp\Bundle\EasyAdminBundle\Field\IdField;
use EasyCorp\Bundle\EasyAdminBundle\Field\IntegerField;
use EasyCorp\Bundle\EasyAdminBundle\Filter\NumericFilter;

class PropertyPlacementStandardPriceCrudController extends AbstractCrudController
{
    public function __construct(
        private readonly CityRepositoryInterface $cityRepository,
    ) {
    }

    public static function getEntityFqcn(): string
    {
        return PropertyPlacementStandardPrice::class;
    }

    public function configureCrud(Crud $crud): Crud
    {
        return $crud
            ->setEntityLabelInSingular('Цена стандартного размещения')
            ->setEntityLabelInPlural('Цены стандартного размещения')
            ->setDefaultSort(['cityId' => 'ASC']);
    }

    public function configureFields(string $pageName): iterable
    {
        yield IdField::new('id', 'ID')->hideOnForm();
        yield IntegerField::new('cityId', 'ID города')
            ->formatValue(function ($value, PropertyPlacementStandardPrice $price) {
                $city = $this->cityRepository->findById($price->getCityId());

                return $city !== null
                    ? sprintf('#%d — %s', $price->getCityId(), $city->getName())
                    : ('#' . $price->getCityId());
            });
        yield IntegerField::new('priceBynPerMonth', 'Цена BYN/мес');
        yield BooleanField::new('isActive', 'Активна');
    }

    public function configureFilters(Filters $filters): Filters
    {
        return $filters
            ->add(NumericFilter::new('cityId'))
            ->add('isActive');
    }

    public function createEntity(string $entityFqcn): PropertyPlacementStandardPrice
    {
        return new PropertyPlacementStandardPrice(0, 0);
    }
}
