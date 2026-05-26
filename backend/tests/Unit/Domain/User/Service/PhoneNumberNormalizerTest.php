<?php

declare(strict_types=1);

namespace App\Tests\Unit\Domain\User\Service;

use App\Domain\User\Service\PhoneNumberNormalizer;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

final class PhoneNumberNormalizerTest extends TestCase
{
    #[DataProvider('validPhonesProvider')]
    public function testNormalizeAcceptsBelarusFormats(string $input, string $expected): void
    {
        self::assertSame($expected, PhoneNumberNormalizer::normalize($input));
    }

    /**
     * @return iterable<string, array{string, string}>
     */
    public static function validPhonesProvider(): iterable
    {
        yield 'E.164 with spaces' => ['+375 29 555 77 22', '+375295557722'];
        yield 'country code without plus' => ['375295557722', '+375295557722'];
        yield 'national mobile without country code' => ['295557722', '+375295557722'];
        yield 'national with leading zero' => ['0295557722', '+375295557722'];
        yield 'national with 80 prefix' => ['80295557722', '+375295557722'];
        yield 'plus without country code' => ['+295557722', '+375295557722'];
    }

    #[DataProvider('invalidPhonesProvider')]
    public function testNormalizeRejectsInvalidPhones(string $input): void
    {
        $this->expectException(\InvalidArgumentException::class);
        PhoneNumberNormalizer::normalize($input);
    }

    /**
     * @return iterable<string, array{string}>
     */
    public static function invalidPhonesProvider(): iterable
    {
        yield 'empty' => [''];
        yield 'too short' => ['12345'];
        yield 'foreign country code' => ['+79001234567'];
        yield 'russian national with 8 trunk' => ['89001234567'];
        yield 'russian national without country code' => ['9001234567'];
        yield 'incomplete belarus number' => ['37529555772'];
    }
}
