<?php

declare(strict_types=1);

namespace App\Tests\Unit\Application\Service;

use App\Application\Service\ArticleHtmlNormalizer;
use App\Application\Service\ArticleTextSanitizer;
use App\Application\Service\StaticPageContentPersistNormalizer;
use App\Domain\Shared\ValueObject\Slug;
use App\Domain\StaticPage\Entity\StaticPage;
use PHPUnit\Framework\TestCase;

final class StaticPageContentPersistNormalizerTest extends TestCase
{
    public function testNormalizesEntityEncodedHtmlAndTypography(): void
    {
        $page = new StaticPage(
            Slug::fromString('test-page'),
            'Заголовок — тест',
            '<p>&lt;h2&gt;Раздел&lt;/h2&gt;</p><p>Текст&nbsp;— продолжение .</p>',
            'Meta — title',
            'Meta — description',
        );

        $normalizer = new StaticPageContentPersistNormalizer(
            new ArticleHtmlNormalizer(),
            new ArticleTextSanitizer(),
        );

        $normalizer->normalize($page);

        self::assertSame('Заголовок – тест', $page->getTitle());
        self::assertSame('<p></p><h2>Раздел</h2><p>Текст – продолжение.</p>', $page->getContent());
        self::assertSame('Meta – title', $page->getMetaTitle());
        self::assertSame('Meta – description', $page->getMetaDescription());
    }
}
