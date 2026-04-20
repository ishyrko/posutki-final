<?php

declare(strict_types=1);

namespace App\Application\Service;

use App\Domain\Article\Entity\Article;

/**
 * HTML + typography normalization for article body fields. Used by Doctrine onFlush and by EasyAdmin
 * before flush so saves still apply when Doctrine would skip UPDATE (no dirty fields).
 */
final class ArticleContentPersistNormalizer
{
    public function __construct(
        private readonly ArticleHtmlNormalizer $articleHtmlNormalizer,
        private readonly ArticleTextSanitizer $articleTextSanitizer,
    ) {
    }

    public function normalize(Article $entity): void
    {
        $htmlNormalized = $this->articleHtmlNormalizer->normalize($entity->getContent());
        $content = $this->articleTextSanitizer->sanitizeHtml($htmlNormalized);

        $title = $this->articleTextSanitizer->sanitizePlainText($entity->getTitle());
        $excerpt = $this->articleTextSanitizer->sanitizePlainText($entity->getExcerpt());

        if ($content !== $entity->getContent()) {
            $entity->setContent($content);
        }

        if ($title !== $entity->getTitle()) {
            $entity->setTitle($title);
        }

        if ($excerpt !== $entity->getExcerpt()) {
            $entity->setExcerpt($excerpt);
        }
    }
}
