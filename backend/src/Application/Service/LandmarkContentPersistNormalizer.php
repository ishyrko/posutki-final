<?php

declare(strict_types=1);

namespace App\Application\Service;

use App\Domain\Property\Entity\Landmark;

/**
 * HTML + typography normalization for landmark text fields (same AI-typography rules as articles).
 * Used by EasyAdmin on save and by the demo landmarks seed command.
 */
final class LandmarkContentPersistNormalizer
{
    public function __construct(
        private readonly ArticleHtmlNormalizer $articleHtmlNormalizer,
        private readonly ArticleTextSanitizer $articleTextSanitizer,
    ) {
    }

    public function normalize(Landmark $entity): void
    {
        $name = $this->articleTextSanitizer->sanitizePlainText($entity->getName());
        if ($name !== $entity->getName()) {
            $entity->setName($name);
        }

        $nameGenitive = $this->articleTextSanitizer->sanitizePlainText($entity->getNameGenitive());
        if ($nameGenitive !== $entity->getNameGenitive()) {
            $entity->setNameGenitive($nameGenitive);
        }

        $short = $entity->getShortDescription();
        if ($short === null || trim($short) === '') {
            if ($short !== null) {
                $entity->setShortDescription(null);
            }
        } else {
            $shortSanitized = $this->articleTextSanitizer->sanitizePlainText($short);
            $entity->setShortDescription($shortSanitized === '' ? null : $shortSanitized);
        }

        $rawDescription = $entity->getDescription();
        if ($rawDescription === null || trim($rawDescription) === '') {
            if ($rawDescription !== null) {
                $entity->setDescription(null);
            }
        } else {
            $htmlNormalized = $this->articleHtmlNormalizer->normalize($rawDescription);
            $sanitized = $this->articleTextSanitizer->sanitizeHtml($htmlNormalized);
            $entity->setDescription(trim($sanitized) === '' ? null : $sanitized);
        }

        $address = $entity->getAddress();
        if ($address === null || trim($address) === '') {
            if ($address !== null) {
                $entity->setAddress(null);
            }
        } else {
            $addressSanitized = $this->articleTextSanitizer->sanitizePlainText($address);
            $entity->setAddress($addressSanitized === '' ? null : $addressSanitized);
        }

        $facts = $entity->getFacts();
        if ($facts !== null) {
            $sanitizedFacts = [];
            foreach ($facts as $fact) {
                if (!is_array($fact)) {
                    continue;
                }

                $label = $this->articleTextSanitizer->sanitizePlainText((string) ($fact['label'] ?? ''));
                $value = $this->articleTextSanitizer->sanitizePlainText((string) ($fact['value'] ?? ''));
                if ($label === '' || $value === '') {
                    continue;
                }

                $sanitizedFacts[] = ['label' => $label, 'value' => $value];
            }

            $entity->setFacts($sanitizedFacts === [] ? null : $sanitizedFacts);
        }

        $guestTips = $entity->getGuestTips();
        if ($guestTips !== null) {
            $sanitizedTips = [];
            foreach ($guestTips as $tip) {
                $line = $this->articleTextSanitizer->sanitizePlainText((string) $tip);
                if ($line !== '') {
                    $sanitizedTips[] = $line;
                }
            }

            $entity->setGuestTips($sanitizedTips === [] ? null : $sanitizedTips);
        }
    }
}
