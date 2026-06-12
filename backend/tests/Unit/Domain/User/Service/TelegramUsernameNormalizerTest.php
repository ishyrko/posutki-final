<?php

declare(strict_types=1);

namespace App\Tests\Unit\Domain\User\Service;

use App\Domain\User\Service\TelegramUsernameNormalizer;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

final class TelegramUsernameNormalizerTest extends TestCase
{
    #[DataProvider('validUsernamesProvider')]
    public function testNormalizeAcceptsValidFormats(string $input, string $expected): void
    {
        self::assertSame($expected, TelegramUsernameNormalizer::normalize($input));
    }

    /**
     * @return iterable<string, array{string, string}>
     */
    public static function validUsernamesProvider(): iterable
    {
        yield 'plain username' => ['my_user', 'my_user'];
        yield 'with at sign' => ['@MyName', 'MyName'];
        yield 't.me link' => ['https://t.me/my_user', 'my_user'];
        yield 'telegram.me link' => ['http://telegram.me/my_user', 'my_user'];
        yield 'short link without scheme' => ['t.me/valid1', 'valid1'];
        yield 'with spaces' => ['  @valid1  ', 'valid1'];
        yield 'minimum length' => ['abcde', 'abcde'];
        yield 'maximum length' => ['abcdefghijklmnopqr0123456789abcd', 'abcdefghijklmnopqr0123456789abcd'];
    }

    public function testNormalizeReturnsNullForEmptyValues(): void
    {
        self::assertNull(TelegramUsernameNormalizer::normalize(null));
        self::assertNull(TelegramUsernameNormalizer::normalize(''));
        self::assertNull(TelegramUsernameNormalizer::normalize('   '));
    }

    #[DataProvider('invalidUsernamesProvider')]
    public function testNormalizeRejectsInvalidUsernames(string $input): void
    {
        $this->expectException(\InvalidArgumentException::class);
        TelegramUsernameNormalizer::normalize($input);
    }

    /**
     * @return iterable<string, array{string}>
     */
    public static function invalidUsernamesProvider(): iterable
    {
        yield 'too short' => ['abcd'];
        yield 'too long' => ['abcdefghijklmnopqr0123456789abcde'];
        yield 'starts with digit' => ['1username'];
        yield 'starts with underscore' => ['_username'];
        yield 'ends with underscore' => ['username_'];
        yield 'cyrillic' => ['пользователь'];
        yield 'spaces inside' => ['user name'];
        yield 'invalid url username' => ['https://t.me/ab'];
    }
}
