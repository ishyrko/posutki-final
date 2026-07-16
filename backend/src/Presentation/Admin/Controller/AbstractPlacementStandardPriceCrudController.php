<?php

declare(strict_types=1);

namespace App\Presentation\Admin\Controller;

use App\Domain\Property\Entity\PropertyPlacementStandardPrice;
use App\Domain\Property\Enum\PropertyType;
use App\Domain\Property\Repository\CityRepositoryInterface;
use App\Domain\Property\Repository\PropertyPlacementStandardPriceRepositoryInterface;
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

abstract class AbstractPlacementStandardPriceCrudController extends AbstractCrudController
{
    public function __construct(
        protected readonly CityRepositoryInterface $cityRepository,
        protected readonly RegionRepositoryInterface $regionRepository,
        protected readonly PropertyRepositoryInterface $propertyRepository,
        protected readonly PropertyPlacementStandardPriceRepositoryInterface $standardPriceRepository,
    ) {
    }

    abstract protected function scopedPropertyType(): string;

    abstract protected function entityLabelSingular(): string;

    abstract protected function entityLabelPlural(): string;

    public static function getEntityFqcn(): string
    {
        return PropertyPlacementStandardPrice::class;
    }

    public function configureCrud(Crud $crud): Crud
    {
        return $crud
            ->setEntityLabelInSingular($this->entityLabelSingular())
            ->setEntityLabelInPlural($this->entityLabelPlural())
            ->setDefaultSort(['id' => 'DESC']);
    }

    public function configureFields(string $pageName): iterable
    {
        yield IdField::new('id', 'ID')->hideOnForm();

        if ($this->scopedPropertyType() === PropertyType::Apartment->value) {
            yield ChoiceField::new('cityId', 'Город')
                ->setChoices($this->getApartmentCityChoices($pageName))
                ->setRequired(true)
                ->onlyOnForms();

            yield IntegerField::new('cityId', 'Город')
                ->hideOnForm()
                ->formatValue(function ($value, PropertyPlacementStandardPrice $price) {
                    if ($price->getCityId() === null) {
                        return '—';
                    }
                    $city = $this->cityRepository->findById($price->getCityId());

                    return $city !== null ? $city->getName() : ('#' . $price->getCityId());
                });
        } else {
            yield ChoiceField::new('regionId', 'Область')
                ->setChoices($this->getHouseRegionChoices($pageName))
                ->setRequired(true)
                ->onlyOnForms();

            yield IntegerField::new('regionId', 'Область')
                ->hideOnForm()
                ->formatValue(function ($value, PropertyPlacementStandardPrice $price) {
                    if ($price->getRegionId() === null) {
                        return '—';
                    }
                    $region = $this->regionRepository->findById($price->getRegionId());

                    return $region !== null ? $region->getName() : ('#' . $price->getRegionId());
                });
        }

        yield IntegerField::new('priceBynPerMonth', 'Цена BYN/мес');
        yield BooleanField::new('isActive', 'Активна');
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

    public function createEntity(string $entityFqcn): PropertyPlacementStandardPrice
    {
        return new PropertyPlacementStandardPrice(
            propertyType: $this->scopedPropertyType(),
            cityId: null,
            regionId: null,
            priceBynPerMonth: 0,
        );
    }

    public function persistEntity(EntityManagerInterface $entityManager, $entityInstance): void
    {
        if ($entityInstance instanceof PropertyPlacementStandardPrice) {
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
    protected function getApartmentCityChoices(string $pageName): array
    {
        $cityIds = $this->propertyRepository->findCityIdsWithListings(PropertyType::Apartment->value);
        $choices = [];

        foreach ($cityIds as $cityId) {
            $city = $this->cityRepository->findById($cityId);
            if ($city === null) {
                continue;
            }
            $choices[$city->getName()] = $cityId;
        }

        if ($pageName === Crud::PAGE_NEW) {
            $choices = $this->excludeConfiguredApartmentCities($choices);
        }

        ksort($choices, SORT_NATURAL | SORT_FLAG_CASE);

        return $choices;
    }

    /**
     * @return array<string, int>
     */
    protected function getHouseRegionChoices(string $pageName): array
    {
        $choices = [];
        foreach ($this->regionRepository->findAll() as $region) {
            $choices[$region->getName()] = $region->getId();
        }

        if ($pageName === Crud::PAGE_NEW) {
            $choices = $this->excludeConfiguredHouseRegions($choices);
        }

        ksort($choices, SORT_NATURAL | SORT_FLAG_CASE);

        return $choices;
    }

    /**
     * @param array<string, int> $choices
     *
     * @return array<string, int>
     */
    private function excludeConfiguredApartmentCities(array $choices): array
    {
        $configuredCityIds = array_flip(
            $this->standardPriceRepository->findConfiguredCityIds(PropertyType::Apartment->value),
        );

        return array_filter(
            $choices,
            static fn (int $cityId): bool => !isset($configuredCityIds[$cityId]),
        );
    }

    /**
     * @param array<string, int> $choices
     *
     * @return array<string, int>
     */
    private function excludeConfiguredHouseRegions(array $choices): array
    {
        $configuredRegionIds = array_flip(
            $this->standardPriceRepository->findConfiguredRegionIds(PropertyType::House->value),
        );

        return array_filter(
            $choices,
            static fn (int $regionId): bool => !isset($configuredRegionIds[$regionId]),
        );
    }
}
