<?php

declare(strict_types=1);

namespace App\Application\Service;

use DOMDocument;
use DOMNode;
use DOMXPath;

/**
 * Reduces common typographic patterns often produced by LLMs (em dashes, curly quotes, stray spaces, NBSP).
 * HTML path only mutates text nodes; script/style/pre/code subtrees are left unchanged.
 */
final class ArticleTextSanitizer
{
    public function sanitizePlainText(string $text): string
    {
        return $this->applyPlainTextRules($text);
    }

    public function sanitizeHtml(string $html): string
    {
        if ($html === '') {
            return $html;
        }

        $dom = new DOMDocument('1.0', 'UTF-8');
        $previousLibxml = libxml_use_internal_errors(true);

        $wrapped = '<!DOCTYPE html><html><head><meta charset="UTF-8"></head><body><div id="article-sanitize-root">'
            . $html
            . '</div></body></html>';

        try {
            $loaded = @$dom->loadHTML($wrapped, LIBXML_HTML_NOIMPLIED | LIBXML_HTML_NODEFDTD);
        } finally {
            libxml_clear_errors();
            libxml_use_internal_errors($previousLibxml);
        }

        if ($loaded === false) {
            return $this->normalizeTypography($html);
        }

        $root = $dom->getElementById('article-sanitize-root');
        if ($root === null) {
            $xp = new DOMXPath($dom);
            $nodes = $xp->query("//*[@id='article-sanitize-root']");
            $root = ($nodes !== false && $nodes->length > 0) ? $nodes->item(0) : null;
        }

        if ($root === null) {
            return $this->normalizeTypography($html);
        }

        $this->sanitizeTextNodesUnder($root);

        $out = '';
        foreach ($root->childNodes as $child) {
            $out .= $dom->saveHTML($child);
        }

        return $out;
    }

    private function applyHtmlTextNodeRules(string $text): string
    {
        if ($text === '') {
            return $text;
        }

        if (preg_match('/^\s+$/u', $text) !== 0) {
            return preg_match('/[ \x{00A0}]/u', $text) === 1 ? ' ' : '';
        }

        return $this->normalizeTypography($text);
    }

    /**
     * @internal Used by migrations to keep rules in one place
     */
    public function applyPlainTextRules(string $text): string
    {
        return trim($this->normalizeTypography($text));
    }

    private function normalizeTypography(string $text): string
    {
        $text = $this->normalizeQuotes($text);

        // NBSP (byte sequence and character) to regular space
        $text = str_replace(["\xc2\xa0", "\u{00A0}"], ' ', $text);

        // Em dash (U+2014) -> en dash (U+2013)
        $text = str_replace("\u{2014}", "\u{2013}", $text);

        // Remove whitespace before punctuation
        $prev = '';
        while ($prev !== $text) {
            $prev = $text;
            $text = (string) preg_replace('/\s+([,.:;!?])/u', '$1', $text);
        }

        // Collapse runs of ASCII spaces (avoid merging intentional newlines in short fields)
        return (string) preg_replace('/ {2,}/u', ' ', $text);
    }

    private function normalizeQuotes(string $text): string
    {
        return str_replace(
            [
                "\u{201C}", "\u{201D}",
                "\u{201E}", "\u{201F}",
                "\u{00AB}", "\u{00BB}",
                "\u{2018}", "\u{2019}",
                "\u{201A}", "\u{201B}",
                "\u{2039}", "\u{203A}",
            ],
            [
                '"', '"',
                '"', '"',
                '"', '"',
                "'", "'",
                "'", "'",
                "'", "'",
            ],
            $text,
        );
    }

    private function sanitizeTextNodesUnder(DOMNode $node): void
    {
        if ($node->nodeType === XML_TEXT_NODE) {
            if ($this->shouldSkipTextNodeAncestors($node)) {
                return;
            }

            $node->nodeValue = $this->applyHtmlTextNodeRules($node->nodeValue ?? '');

            return;
        }

        foreach ($node->childNodes as $child) {
            $this->sanitizeTextNodesUnder($child);
        }
    }

    private function shouldSkipTextNodeAncestors(DOMNode $textNode): bool
    {
        $p = $textNode->parentNode;
        while ($p !== null) {
            if ($p->nodeType === XML_ELEMENT_NODE) {
                $name = strtolower($p->nodeName);
                if (\in_array($name, ['script', 'style', 'pre', 'code'], true)) {
                    return true;
                }
            }
            $p = $p->parentNode;
        }

        return false;
    }
}
