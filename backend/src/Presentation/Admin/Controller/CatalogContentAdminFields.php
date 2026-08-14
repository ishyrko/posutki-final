<?php

declare(strict_types=1);

namespace App\Presentation\Admin\Controller;

use App\Application\Service\CatalogPlaceContentNormalizer;
use App\Presentation\Admin\Form\CatalogFaqItemType;
use EasyCorp\Bundle\EasyAdminBundle\Config\Assets;
use EasyCorp\Bundle\EasyAdminBundle\Field\CollectionField;
use EasyCorp\Bundle\EasyAdminBundle\Field\TextareaField;

final class CatalogContentAdminFields
{
    public static function configureAssets(Assets $assets): Assets
    {
        return $assets
            ->addJsFile('https://cdn.jsdelivr.net/npm/tinymce@7/tinymce.min.js')
            ->addJsFile('js/admin-article-tinymce.js')
            ->addCssFile('css/admin-catalog-faq.css');
    }

    public static function seoTextField(string $label = 'SEO-текст'): TextareaField
    {
        return TextareaField::new('catalogSeoText', $label)
            ->setFormTypeOption('attr', [
                'class' => 'ea-static-page-content-rte form-control',
                'rows' => 16,
                'data-upload-scope' => 'static-pages',
            ])
            ->hideOnIndex();
    }

    public static function faqField(): CollectionField
    {
        return CollectionField::new('catalogFaq', 'FAQ')
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

    public static function normalize(object $entity, CatalogPlaceContentNormalizer $normalizer): void
    {
        if (!method_exists($entity, 'getCatalogSeoText') || !method_exists($entity, 'setCatalogSeoText')) {
            return;
        }

        /** @var callable $getSeoText */
        $getSeoText = [$entity, 'getCatalogSeoText'];
        /** @var callable $setSeoText */
        $setSeoText = [$entity, 'setCatalogSeoText'];
        $setSeoText($normalizer->normalizeSeoText($getSeoText()));

        if (!method_exists($entity, 'getCatalogFaq') || !method_exists($entity, 'setCatalogFaq')) {
            return;
        }

        /** @var callable $getFaq */
        $getFaq = [$entity, 'getCatalogFaq'];
        /** @var callable $setFaq */
        $setFaq = [$entity, 'setCatalogFaq'];

        $faq = $getFaq();
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

        $setFaq($normalizer->normalizeFaq($faq));
    }
}
