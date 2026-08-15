<?php

declare(strict_types=1);

namespace App\Presentation\Admin\Controller;

use App\Application\Service\CatalogPlaceContentNormalizer;
use App\Domain\Property\Entity\CityRoomCatalogContent;
use App\Domain\Property\Repository\CityRepositoryInterface;
use App\Domain\Property\Service\CatalogApartmentCitySlugs;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\QueryBuilder;
use EasyCorp\Bundle\EasyAdminBundle\Collection\FieldCollection;
use EasyCorp\Bundle\EasyAdminBundle\Collection\FilterCollection;
use EasyCorp\Bundle\EasyAdminBundle\Config\Crud;
use EasyCorp\Bundle\EasyAdminBundle\Config\Filters;
use EasyCorp\Bundle\EasyAdminBundle\Controller\AbstractCrudController;
use EasyCorp\Bundle\EasyAdminBundle\Dto\EntityDto;
use EasyCorp\Bundle\EasyAdminBundle\Dto\SearchDto;
use EasyCorp\Bundle\EasyAdminBundle\Field\ChoiceField;
use EasyCorp\Bundle\EasyAdminBundle\Field\IdField;
use EasyCorp\Bundle\EasyAdminBundle\Field\IntegerField;
use EasyCorp\Bundle\EasyAdminBundle\Field\TextField;
use EasyCorp\Bundle\EasyAdminBundle\Filter\ChoiceFilter;

class CityRoomCatalogContentCrudController extends AbstractCrudController
{
    /** @var array<string, int> */
    private const ROOM_BUCKET_CHOICES = [
        '1-комнатные' => 1,
        '2-комнатные' => 2,
        '3-комнатные' => 3,
    ];

    public function __construct(
        private readonly CatalogPlaceContentNormalizer $catalogPlaceContentNormalizer,
        private readonly CityRepositoryInterface $cityRepository,
    ) {
    }

    public static function getEntityFqcn(): string
    {
        return CityRoomCatalogContent::class;
    }

    public function configureCrud(Crud $crud): Crud
    {
        return $crud
            ->setEntityLabelInSingular('SEO по комнатам')
            ->setEntityLabelInPlural('SEO по комнатам')
            ->setSearchFields(['roomsBucket']);
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
            ->addSelect(sprintf('%s.roomsBucket AS HIDDEN roomBucketSort', $rootAlias))
            ->orderBy('roomBucketSort', 'ASC');
    }

    public function configureAssets(\EasyCorp\Bundle\EasyAdminBundle\Config\Assets $assets): \EasyCorp\Bundle\EasyAdminBundle\Config\Assets
    {
        return CatalogContentAdminFields::configureAssets($assets);
    }

    public function persistEntity(EntityManagerInterface $entityManager, $entityInstance): void
    {
        $this->normalizeEntity($entityInstance);
        parent::persistEntity($entityManager, $entityInstance);
    }

    public function updateEntity(EntityManagerInterface $entityManager, $entityInstance): void
    {
        $this->normalizeEntity($entityInstance);
        parent::updateEntity($entityManager, $entityInstance);
    }

    public function configureFields(string $pageName): iterable
    {
        yield IdField::new('id', 'ID')->hideOnForm();

        yield IntegerField::new('cityId', 'ID города')
            ->setFormTypeOption('attr', ['readonly' => true])
            ->formatValue(fn (?int $cityId): string => $this->formatCityLabel($cityId));

        yield ChoiceField::new('roomsBucket', 'Комнатность')
            ->setChoices(self::ROOM_BUCKET_CHOICES)
            ->renderAsBadges()
            ->setFormTypeOption('disabled', Crud::PAGE_NEW !== $pageName);

        yield CatalogContentAdminFields::visibilityField();
        yield CatalogContentAdminFields::seoTextField('SEO-текст под каталогом по комнатам');
        yield CatalogContentAdminFields::faqField();
    }

    public function configureFilters(Filters $filters): Filters
    {
        return $filters
            ->add(ChoiceFilter::new('roomsBucket', 'Комнатность')->setChoices(self::ROOM_BUCKET_CHOICES));
    }

    private function normalizeEntity(object $entity): void
    {
        if (!$entity instanceof CityRoomCatalogContent) {
            return;
        }

        CatalogContentAdminFields::normalize($entity, $this->catalogPlaceContentNormalizer);
        CatalogContentAdminFields::refreshFaqReference($entity);
    }

    private function formatCityLabel(?int $cityId): string
    {
        if ($cityId === null) {
            return '—';
        }

        $city = $this->cityRepository->findById($cityId);
        if ($city === null) {
            return (string) $cityId;
        }

        $bucketHint = CatalogApartmentCitySlugs::supportsSlug($city->getSlug()) ? '' : ' (вне каталога)';

        return sprintf('%s (%s)%s', $city->getName(), $city->getSlug(), $bucketHint);
    }
}
