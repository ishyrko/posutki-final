<?php

declare(strict_types=1);

namespace App\Tests\Unit\Domain\Property\Validation;

use App\Domain\Property\Validation\PropertyVideoUrlValidator;
use App\Domain\Shared\Exception\DomainException;
use PHPUnit\Framework\TestCase;

final class PropertyVideoUrlValidatorTest extends TestCase
{
    public function testAllowsNullAndEmpty(): void
    {
        PropertyVideoUrlValidator::assertValid(null);
        PropertyVideoUrlValidator::assertValid('');
        PropertyVideoUrlValidator::assertValid('   ');
        $this->addToAssertionCount(1);
    }

    public function testAllowsYoutubeUrls(): void
    {
        PropertyVideoUrlValidator::assertValid('https://www.youtube.com/watch?v=dQw4w9WgXcQ');
        PropertyVideoUrlValidator::assertValid('https://youtu.be/dQw4w9WgXcQ');
        $this->addToAssertionCount(1);
    }

    public function testAllowsTiktokUrls(): void
    {
        PropertyVideoUrlValidator::assertValid('https://www.tiktok.com/@user/video/7123456789012345678');
        PropertyVideoUrlValidator::assertValid('https://vm.tiktok.com/@user/video/7123456789012345678');
        $this->addToAssertionCount(1);
    }

    public function testRejectsUnsupportedHost(): void
    {
        $this->expectException(DomainException::class);
        PropertyVideoUrlValidator::assertValid('https://example.com/video/123');
    }
}
