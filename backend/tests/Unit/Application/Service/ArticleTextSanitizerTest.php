<?php

declare(strict_types=1);

namespace App\Tests\Unit\Application\Service;

use App\Application\Service\ArticleTextSanitizer;
use PHPUnit\Framework\TestCase;

final class ArticleTextSanitizerTest extends TestCase
{
    private ArticleTextSanitizer $sanitizer;

    protected function setUp(): void
    {
        $this->sanitizer = new ArticleTextSanitizer();
    }

    public function testSanitizeHtmlPreservesSpaceBeforeInlineTag(): void
    {
        $html = '<p>слово <strong>жирный</strong> текст</p>';

        self::assertSame(
            '<p>слово <strong>жирный</strong> текст</p>',
            $this->sanitizer->sanitizeHtml($html),
        );
    }

    public function testSanitizeHtmlPreservesSpaceBetweenInlineTags(): void
    {
        $html = '<p><a href="/test">ссылка</a> <em>курсив</em></p>';

        self::assertSame(
            '<p><a href="/test">ссылка</a> <em>курсив</em></p>',
            $this->sanitizer->sanitizeHtml($html),
        );
    }

    public function testSanitizePlainTextStillTrimsEdges(): void
    {
        self::assertSame('Заголовок', $this->sanitizer->sanitizePlainText('  Заголовок  '));
    }

    public function testSanitizePlainTextReplacesCurlyQuotesWithStraightQuotes(): void
    {
        self::assertSame(
            '"обычные" и \'одинарные\' кавычки',
            $this->sanitizer->sanitizePlainText("“обычные” и ‘одинарные’ кавычки"),
        );
    }

    public function testSanitizeHtmlReplacesGuillemetsWithStraightQuotes(): void
    {
        $html = '<p>Текст «в кавычках» и <strong>«жирный»</strong></p>';

        self::assertSame(
            '<p>Текст "в кавычках" и <strong>"жирный"</strong></p>',
            $this->sanitizer->sanitizeHtml($html),
        );
    }
}
