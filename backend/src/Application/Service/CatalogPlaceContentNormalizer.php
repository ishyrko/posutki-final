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
                'question' => $question,
                'answer' => $this->articleTextSanitizer->sanitizePlainText($answer),
            ];
        }

        return $normalized === [] ? null : $normalized;
    }
}
