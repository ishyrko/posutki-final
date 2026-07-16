<?php

declare(strict_types=1);

namespace App\Presentation\Admin\Controller;

use App\Application\Service\PropertyPlacementService;
use App\Domain\Property\Entity\PropertyPlacementSlot;
use App\Domain\Property\Enum\PropertyType;
use App\Domain\Property\Repository\CityRepositoryInterface;
use App\Domain\Property\Repository\PropertyRepositoryInterface;
use App\Domain\Property\Repository\RegionRepositoryInterface;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\QueryBuilder;
use EasyCorp\Bundle\EasyAdminBundle\Collection\FieldCollection;
use EasyCorp\Bundle\EasyAdminBundle\Collection\FilterCollection;
use EasyCorp\Bundle\EasyAdminBundle\Config\Crud;
use EasyCorp\Bundle\EasyAdminBundle\Config\Filters;
use EasyCorp\Bundle\EasyAdminBundle\Controller\AbstractCrudController;
use EasyCorp\Bundle\EasyAdminBundle\Dto\EntityDto;
use EasyCorp\Bundle\EasyAdminBundle\Dto\SearchDto;
use EasyCorp\Bundle\EasyAdminBundle\Field\BooleanField;
use EasyCorp\Bundle\EasyAdminBundle\Field\ChoiceField;
use EasyCorp\Bundle\EasyAdminBundle\Field\IdField;
use EasyCorp\Bundle\EasyAdminBundle\Field\IntegerField;
use EasyCorp\Bundle\EasyAdminBundle\Filter\BooleanFilter;

abstract class AbstractPlacementSlotCrudController extends AbstractCrudController
{
    public function __construct(
        protected readonly PropertyPlacementService $placementService,
        protected readonly CityRepositoryInterface $cityRepository,
        protected readonly RegionRepositoryInterface $regionRepository,
        protected readonly PropertyRepositoryInterface $propertyRepository,
    ) {
    }

    abstract protected function scopedPropertyType(): string;

    abstract protected function entityLabelSingular(): string;

    abstract protected function entityLabelPlural(): string;

    public static function getEntityFqcn(): string
    {
        return PropertyPlacementSlot::class;
    }

    public function configureCrud(Crud $crud): Crud
    {
        return $crud
            ->setEntityLabelInSingular($this->entityLabelSingular())
            ->setEntityLabelInPlural($this->entityLabelPlural())
            ->setDefaultSort(['sortOrder' => 'ASC', 'rankFrom' => 'ASC']);
    }

    public function configureFields(string $pageName): iterable
    {
        yield IdField::new('id', 'ID')->hideOnForm();

        if ($this->scopedPropertyType() === PropertyType::Apartment->value) {
            yield ChoiceField::new('cityId', 'Город')
                ->setChoices($this->getApartmentCityChoices())
                ->setRequired(true)
                ->onlyOnForms();

            yield IntegerField::new('cityId', 'Город')
                ->hideOnForm()
                ->formatValue(function ($value, PropertyPlacementSlot $slot) {
                    if ($slot->getCityId() === null) {
                        return '—';
                    }
                    $city = $this->cityRepository->findById($slot->getCityId());

                    return $city !== null ? $city->getName() : ('#' . $slot->getCityId());
                });
        } else {
            yield ChoiceField::new('regionId', 'Область')
                ->setChoices($this->getHouseRegionChoices())
                ->setRequired(true)
                ->onlyOnForms();

            yield IntegerField::new('regionId', 'Область')
                ->hideOnForm()
                ->formatValue(function ($value, PropertyPlacementSlot $slot) {
                    if ($slot->getRegionId() === null) {
                        return '—';
                    }
                    $region = $this->regionRepository->findById($slot->getRegionId());

                    return $region !== null ? $region->getName() : ('#' . $slot->getRegionId());
                });
        }

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
        return $filters->add(BooleanFilter::new('isActive'));
    }

    public function createIndexQueryBuilder(
        SearchDto $searchDto,
        EntityDto $entityDto,
        FieldCollection $fields,
        FilterCollection $filters,
    ): QueryBuilder {
        $qb = parent::createIndexQueryBuilder($searchDto, $entityDto, $fields, $filters);
        $rootAlias = $qb->getRootAliases()[0];

        return $qb
            ->andWhere(sprintf('%s.propertyType = :scopedPropertyType', $rootAlias))
            ->setParameter('scopedPropertyType', $this->scopedPropertyType());
    }

    public function createEntity(string $entityFqcn): PropertyPlacementSlot
    {
        return new PropertyPlacementSlot(
            propertyType: $this->scopedPropertyType(),
            cityId: null,
            regionId: null,
            rankFrom: 1,
            rankTo: 1,
            capacity: 1,
            priceBynPerMonth: 0,
        );
    }

    public function persistEntity(EntityManagerInterface $entityManager, $entityInstance): void
    {
        if ($entityInstance instanceof PropertyPlacementSlot) {
            $entityInstance->setPropertyType($this->scopedPropertyType());
            if ($this->scopedPropertyType() === PropertyType::Apartment->value) {
                $entityInstance->setRegionId(null);
            } else {
                $entityInstance->setCityId(null);
            }
            $entityInstance->validate();
        }

        parent::persistEntity($entityManager, $entityInstance);
    }

    public function updateEntity(EntityManagerInterface $entityManager, $entityInstance): void
    {
        $this->persistEntity($entityManager, $entityInstance);
    }

    /**
     * @return array<string, int>
     */
    protected function getApartmentCityChoices(): array
    {
        $choices = [];
        foreach ($this->propertyRepository->findCityIdsWithListings(PropertyType::Apartment->value) as $cityId) {
            $city = $this->cityRepository->findById($cityId);
            if ($city === null) {
                continue;
            }
            $choices[$city->getName()] = $cityId;
        }

        ksort($choices, SORT_NATURAL | SORT_FLAG_CASE);

        return $choices;
    }

    /**
     * @return array<string, int>
     */
    protected function getHouseRegionChoices(): array
    {
        $choices = [];
        foreach ($this->regionRepository->findAll() as $region) {
            $choices[$region->getName()] = $region->getId();
        }

        ksort($choices, SORT_NATURAL | SORT_FLAG_CASE);

        return $choices;
    }
}
