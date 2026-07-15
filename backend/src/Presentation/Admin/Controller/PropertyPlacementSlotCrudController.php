<?php

declare(strict_types=1);

namespace App\Presentation\Admin\Controller;

use App\Application\Service\PropertyPlacementService;
use App\Domain\Property\Entity\PropertyPlacementSlot;
use App\Domain\Property\Repository\CityRepositoryInterface;
use EasyCorp\Bundle\EasyAdminBundle\Config\Crud;
use EasyCorp\Bundle\EasyAdminBundle\Config\Filters;
use EasyCorp\Bundle\EasyAdminBundle\Controller\AbstractCrudController;
use EasyCorp\Bundle\EasyAdminBundle\Field\BooleanField;
use EasyCorp\Bundle\EasyAdminBundle\Field\IdField;
use EasyCorp\Bundle\EasyAdminBundle\Field\IntegerField;
use EasyCorp\Bundle\EasyAdminBundle\Filter\NumericFilter;

class PropertyPlacementSlotCrudController extends AbstractCrudController
{
    public function __construct(
        private readonly PropertyPlacementService $placementService,
        private readonly CityRepositoryInterface $cityRepository,
    ) {
    }

    public static function getEntityFqcn(): string
    {
        return PropertyPlacementSlot::class;
    }

    public function configureCrud(Crud $crud): Crud
    {
        return $crud
            ->setEntityLabelInSingular('Диапазон позиций')
            ->setEntityLabelInPlural('Диапазоны позиций (спецразмещение)')
            ->setDefaultSort(['cityId' => 'ASC', 'sortOrder' => 'ASC'])
            ->setSearchFields(['cityId']);
    }

    public function configureFields(string $pageName): iterable
    {
        yield IdField::new('id', 'ID')->hideOnForm();
        yield IntegerField::new('cityId', 'ID города')
            ->formatValue(function ($value, PropertyPlacementSlot $slot) {
                $city = $this->cityRepository->findById($slot->getCityId());

                return $city !== null
                    ? sprintf('#%d — %s', $slot->getCityId(), $city->getName())
                    : ('#' . $slot->getCityId());
            });
        yield IntegerField::new('rankFrom', 'Позиция с');
        yield IntegerField::new('rankTo', 'Позиция по');
        yield IntegerField::new('capacity', 'Вместимость')
            ->formatValue(function ($value, PropertyPlacementSlot $slot) {
                $occupied = $this->placementService->getSlotOccupancy($slot);

                return sprintf('%d (занято %d)', $slot->getCapacity(), $occupied);
            });
        yield IntegerField::new('priceBynPerMonth', 'Цена BYN/мес');
        yield BooleanField::new('isTopSlot', 'Топ-слот');
        yield IntegerField::new('sortOrder', 'Порядок');
        yield BooleanField::new('isActive', 'Активен');
    }

    public function configureFilters(Filters $filters): Filters
    {
        return $filters
            ->add(NumericFilter::new('cityId'))
            ->add('isActive');
    }

    public function createEntity(string $entityFqcn): PropertyPlacementSlot
    {
        return new PropertyPlacementSlot(0, 1, 1, 1, 0);
    }
}
