<?php

declare(strict_types=1);

namespace App\Application\Service;

/**
 * Strips double-escaped markup (&lt;h2&gt; inside &lt;p&gt;) that TinyMCE/forms sometimes store as text.
 * Only safe for trusted content from admin/API.
 */
final class ArticleHtmlNormalizer
{
    public function normalize(string $content): string
    {
        if (!$this->hasEntityEncodedTags($content)) {
            return $content;
        }

        $prev = '';
        $out = $content;
        while ($out !== $prev) {
            $prev = $out;
            $out = html_entity_decode($out, ENT_QUOTES | ENT_HTML5, 'UTF-8');
        }

        return $out;
    }

    private function hasEntityEncodedTags(string $s): bool
    {
        return 1 === preg_match('/&lt;[a-z][\s\S]*?&gt;/i', $s)
            || 1 === preg_match('/&lt;\/[a-z]/i', $s);
    }
}
