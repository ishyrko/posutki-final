<?php

declare(strict_types=1);

namespace App\Infrastructure\Import;

/**
 * Drops partner-site chrome that scrapers sometimes treat as listing photos
 * (Arendom header Instagram PNG on every card).
 */
final class PartnerJunkImageDetector
{
    /** MD5 of arendom.com header file 1684702232_instagramm.png */
    private const JUNK_MD5 = [
        '75bf81195ba4f2b5281194c56001e931',
    ];

    private const JUNK_NAME = '/instagramm?|whatsapp|viber|telegram|facebook|favicon|sprite/i';

    public function isJunk(string $path): bool
    {
        if ($path === '') {
            return false;
        }

        if (preg_match(self::JUNK_NAME, $path) === 1) {
            return true;
        }

        if (!is_file($path)) {
            return false;
        }

        $hash = md5_file($path);

        return $hash !== false && in_array($hash, self::JUNK_MD5, true);
    }
}
