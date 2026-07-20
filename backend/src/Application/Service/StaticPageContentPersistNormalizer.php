<?php

declare(strict_types=1);

namespace App\Application\Service;

use App\Domain\StaticPage\Entity\StaticPage;

/**
 * HTML + typography normalization for static page fields. Used by Doctrine onFlush and by EasyAdmin
 * before flush so saves still apply when Doctrine would skip UPDATE (no dirty fields).
 */
final class StaticPageContentPersistNormalizer
{
    public function __construct(
        private readonly ArticleHtmlNormalizer $articleHtmlNormalizer,
        private readonly ArticleTextSanitizer $articleTextSanitizer,
    ) {
    }

    public function normalize(StaticPage $entity): void
    {
        $htmlNormalized = $this->articleHtmlNormalizer->normalize($entity->getContent());
        $content = $this->articleTextSanitizer->sanitizeHtml($htmlNormalized);

        $title = $this->articleTextSanitizer->sanitizePlainText($entity->getTitle());
        $metaTitle = $entity->getMetaTitle();
        $metaTitleSanitized = $metaTitle === null
            ? null
            : $this->articleTextSanitizer->sanitizePlainText($metaTitle);
        $metaDescription = $entity->getMetaDescription();
        $metaDescriptionSanitized = $metaDescription === null
            ? null
            : $this->articleTextSanitizer->sanitizePlainText($metaDescription);

        if ($content !== $entity->getContent()) {
            $entity->setContent($content);
        }

        if ($title !== $entity->getTitle()) {
            $entity->setTitle($title);
        }

        if ($metaTitleSanitized !== $entity->getMetaTitle()) {
            $entity->setMetaTitle($metaTitleSanitized);
        }

        if ($metaDescriptionSanitized !== $entity->getMetaDescription()) {
            $entity->setMetaDescription($metaDescriptionSanitized);
        }
    }
}
