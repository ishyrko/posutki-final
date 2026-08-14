<?php

declare(strict_types=1);

namespace App\Presentation\Admin\Controller;

use App\Application\Service\CatalogPlaceContentNormalizer;
use App\Domain\Property\Entity\CityDistrict;
use App\Domain\Property\Entity\CityMicrodistrict;
use App\Domain\Property\Entity\ResidentialComplex;
use App\Presentation\Admin\Form\CatalogFaqItemType;
use Doctrine\ORM\EntityManagerInterface;
use EasyCorp\Bundle\EasyAdminBundle\Config\Assets;
use EasyCorp\Bundle\EasyAdminBundle\Controller\AbstractCrudController;
use EasyCorp\Bundle\EasyAdminBundle\Field\CollectionField;
use EasyCorp\Bundle\EasyAdminBundle\Field\IdField;
use EasyCorp\Bundle\EasyAdminBundle\Field\IntegerField;
use EasyCorp\Bundle\EasyAdminBundle\Field\TextareaField;
use EasyCorp\Bundle\EasyAdminBundle\Field\TextField;

abstract class AbstractCatalogPlaceCrudController extends AbstractCrudController
{
    public function __construct(
        protected readonly CatalogPlaceContentNormalizer $catalogPlaceContentNormalizer,
    ) {
    }

    public function configureAssets(Assets $assets): Assets
    {
        return $assets
            ->addJsFile('https://cdn.jsdelivr.net/npm/tinymce@7/tinymce.min.js')
            ->addJsFile('js/admin-article-tinymce.js')
            ->addCssFile('css/admin-catalog-faq.css');
    }

    public function persistEntity(EntityManagerInterface $entityManager, $entityInstance): void
    {
        $this->normalizeCatalogContent($entityInstance);
        parent::persistEntity($entityManager, $entityInstance);
    }

    public function updateEntity(EntityManagerInterface $entityManager, $entityInstance): void
    {
        $this->normalizeCatalogContent($entityInstance);
        parent::updateEntity($entityManager, $entityInstance);
    }

    /**
     * @return iterable<int, mixed>
     */
    protected function catalogPlaceFields(): iterable
    {
        yield IdField::new('id', 'ID')->hideOnForm();
        yield IntegerField::new('cityId', 'ID города');
        yield TextField::new('officialName', 'Официальное название');
        yield TextField::new('name', 'Название');
        yield TextField::new('namePrepositional', 'Предложный падеж');
        yield TextField::new('slug', 'Slug');
        yield TextareaField::new('catalogSeoText', 'SEO-текст')
            ->setFormTypeOption('attr', [
                'class' => 'ea-static-page-content-rte form-control',
                'rows' => 16,
                'data-upload-scope' => 'static-pages',
            ])
            ->hideOnIndex();
        yield CollectionField::new('catalogFaq', 'FAQ')
            ->setEntryType(CatalogFaqItemType::class)
            ->setEntryIsComplex()
            ->renderExpanded()
            ->allowAdd()
            ->allowDelete()
            ->showEntryLabel()
            ->setEntryToStringMethod(static function (?array $item): string {
                if (!is_array($item)) {
                    return 'Новый вопрос';
                }

                $question = isset($item['question']) && is_string($item['question']) ? trim($item['question']) : '';

                return $question !== '' ? $question : 'Новый вопрос';
            })
            ->setFormTypeOption('by_reference', false)
            ->setFormTypeOption('attr', ['data-ea-collection-field' => 'catalogFaq'])
            ->setHelp('Добавьте пары «вопрос — ответ» для блока FAQ под каталогом.')
            ->hideOnDetail()
            ->formatValue(static fn (?array $value): string => $value === null || $value === [] ? '—' : (string) count($value));
    }

    private function normalizeCatalogContent(object $entity): void
    {
        if (!$entity instanceof CityDistrict && !$entity instanceof CityMicrodistrict && !$entity instanceof ResidentialComplex) {
            return;
        }

        $entity->setCatalogSeoText($this->catalogPlaceContentNormalizer->normalizeSeoText($entity->getCatalogSeoText()));

        $faq = $entity->getCatalogFaq();
        if (is_string($faq)) {
            $decoded = json_decode($faq, true);
            $faq = is_array($decoded) ? $decoded : null;
        }
        if (is_array($faq)) {
            $faq = array_values(array_filter(
                $faq,
                static fn ($item): bool => is_array($item),
            ));
        }
        $entity->setCatalogFaq($this->catalogPlaceContentNormalizer->normalizeFaq($faq));
    }
}
