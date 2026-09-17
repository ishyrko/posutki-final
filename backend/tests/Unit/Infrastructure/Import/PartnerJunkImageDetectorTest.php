<?php

declare(strict_types=1);

namespace App\Tests\Unit\Infrastructure\Import;

use App\Infrastructure\Import\PartnerJunkImageDetector;
use PHPUnit\Framework\TestCase;

final class PartnerJunkImageDetectorTest extends TestCase
{
    public function testDetectsInstagramFilename(): void
    {
        $detector = new PartnerJunkImageDetector();

        self::assertTrue($detector->isJunk('images/1684702232_instagramm.png'));
        self::assertTrue($detector->isJunk('/tmp/instagram-header.png'));
        self::assertFalse($detector->isJunk('images/10908/08.png'));
    }

    public function testOrdinaryFileIsNotJunk(): void
    {
        $detector = new PartnerJunkImageDetector();
        $path = tempnam(sys_get_temp_dir(), 'impimg');
        self::assertNotFalse($path);
        file_put_contents($path, 'ordinary-listing-photo');

        try {
            self::assertFalse($detector->isJunk($path));
        } finally {
            unlink($path);
        }
    }

    public function testDetectsKnownArendomInstagramHash(): void
    {
        $source = __DIR__ . '/fixtures/arendom-instagram.png';
        self::assertFileExists($source);
        self::assertSame('75bf81195ba4f2b5281194c56001e931', md5_file($source));

        $path = tempnam(sys_get_temp_dir(), 'impimg');
        self::assertNotFalse($path);
        copy($source, $path);

        try {
            self::assertTrue((new PartnerJunkImageDetector())->isJunk($path));
        } finally {
            unlink($path);
        }
    }
}
