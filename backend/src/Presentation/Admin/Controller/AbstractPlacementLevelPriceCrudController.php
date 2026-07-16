<?php

declare(strict_types=1);

namespace App\Presentation\Admin\Controller;

use App\Application\Service\PropertyPlacementService;
use App\Domain\Property\Entity\PropertyPlacementLevelPrice;
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
use EasyCorp\Bundle\EasyAdminBundle\Filter\NumericFilter;

abstract class AbstractPlacementLevelPriceCrudController extends AbstractCrudController
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
        return PropertyPlacementLevelPrice::class;
    }

    public function configureCrud(Crud $crud): Crud
    {
        return $crud
            ->setEntityLabelInSingular($this->entityLabelSingular())
            ->setEntityLabelInPlural($this->entityLabelPlural())
            ->setDefaultSort(['sortOrder' => 'ASC', 'level' => 'DESC']);
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
                ->formatValue(function ($value, PropertyPlacementLevelPrice $levelPrice) {
                    if ($levelPrice->getCityId() === null) {
                        return '—';
                    }
                    $city = $this->cityRepository->findById($levelPrice->getCityId());

                    return $city !== null ? $city->getName() : ('#' . $levelPrice->getCityId());
                });
        } else {
            yield ChoiceField::new('regionId', 'Область')
                ->setChoices($this->getHouseRegionChoices())
                ->setRequired(true)
                ->onlyOnForms();

            yield IntegerField::new('regionId', 'Область')
                ->hideOnForm()
                ->formatValue(function ($value, PropertyPlacementLevelPrice $levelPrice) {
                    if ($levelPrice->getRegionId() === null) {
                        return '—';
                    }
                    $region = $this->regionRepository->findById($levelPrice->getRegionId());

                    return $region !== null ? $region->getName() : ('#' . $levelPrice->getRegionId());
                });
        }

        yield IntegerField::new('level', 'VIP-уровень')
            ->setHelp(sprintf('От %d до %d', PropertyPlacementLevelPrice::MIN_LEVEL, PropertyPlacementLevelPrice::MAX_LEVEL));
        yield IntegerField::new('capacity', 'Лимит мест')
            ->setHelp('Оставьте пустым, если без ограничения')
            ->setRequired(false)
            ->formatValue(function ($value, PropertyPlacementLevelPrice $levelPrice) {
                if ($levelPrice->getCapacity() === null) {
                    return 'без лимита';
                }
                $occupied = $this->placementService->getLevelPriceOccupancy($levelPrice);

                return sprintf('%d (занято %d)', $levelPrice->getCapacity(), $occupied);
            });
        yield IntegerField::new('priceBynPerMonth', 'Цена BYN/мес');
        yield IntegerField::new('sortOrder', 'Порядок');
        yield BooleanField::new('isActive', 'Активен');
    }

    public function configureFilters(Filters $filters): Filters
    {
        return $filters
            ->add(BooleanFilter::new('isActive'))
            ->add(NumericFilter::new('level', 'VIP-уровень'));
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

    public function createEntity(string $entityFqcn): PropertyPlacementLevelPrice
    {
        return new PropertyPlacementLevelPrice(
            propertyType: $this->scopedPropertyType(),
            cityId: null,
            regionId: null,
            level: PropertyPlacementLevelPrice::MIN_LEVEL,
            priceBynPerMonth: 0,
        );
    }

    public function persistEntity(EntityManagerInterface $entityManager, $entityInstance): void
    {
        if ($entityInstance instanceof PropertyPlacementLevelPrice) {
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
