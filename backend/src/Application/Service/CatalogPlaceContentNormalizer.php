<?php

declare(strict_types=1);

namespace App\Application\Service;

use App\Application\Service\ArticleHtmlNormalizer;
use App\Application\Service\ArticleTextSanitizer;

final class CatalogPlaceContentNormalizer
{
    public function __construct(
        private readonly ArticleHtmlNormalizer $articleHtmlNormalizer,
        private readonly ArticleTextSanitizer $articleTextSanitizer,
    ) {
    }

    public function normalizeEntity(object $entity): void
    {
        if (!method_exists($entity, 'getCatalogSeoText') || !method_exists($entity, 'setCatalogSeoText')) {
            return;
        }

        /** @var callable $getSeoText */
        $getSeoText = [$entity, 'getCatalogSeoText'];
        /** @var callable $setSeoText */
        $setSeoText = [$entity, 'setCatalogSeoText'];
        $setSeoText($this->normalizeSeoText($getSeoText()));

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

        $setFaq($this->normalizeFaq($faq));
    }

    public function normalizeSeoText(?string $raw): ?string
    {
        if ($raw === null || trim($raw) === '') {
            return null;
        }

        $htmlNormalized = $this->articleHtmlNormalizer->normalize($raw);
        $sanitized = $this->articleTextSanitizer->sanitizeHtml($htmlNormalized);

        return trim($sanitized) === '' ? null : $sanitized;
    }

    /**
     * @param list<array{question?: mixed, answer?: mixed}>|null $raw
     * @return list<array{question: string, answer: string}>|null
     */
    public function normalizeFaq(?array $raw): ?array
    {
        if ($raw === null || $raw === []) {
            return null;
        }

        $normalized = [];
        foreach ($raw as $item) {
            if (!is_array($item)) {
                continue;
            }
            $question = isset($item['question']) && is_string($item['question']) ? trim($item['question']) : '';
            $answer = isset($item['answer']) && is_string($item['answer']) ? trim($item['answer']) : '';
            if ($question === '' || $answer === '') {
                continue;
            }
            $normalized[] = [
                'question' => $this->articleTextSanitizer->sanitizePlainText($question),
                'answer' => $this->articleTextSanitizer->sanitizePlainText($answer),
            ];
        }

        return $normalized === [] ? null : $normalized;
    }
}
