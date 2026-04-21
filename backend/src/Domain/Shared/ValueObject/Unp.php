<?php

declare(strict_types=1);

namespace App\Domain\Shared\ValueObject;

use App\Domain\Shared\Exception\DomainException;

final readonly class Unp
{
    private function __construct(
        private string $value
    ) {
    }

    public static function fromString(string $raw): self
    {
        $normalized = strtoupper(trim($raw));
        if ($normalized === '' || strlen($normalized) !== 9 || !preg_match('/^[0-9A-Z]{9}$/', $normalized)) {
            throw new DomainException('УНП: ровно 9 символов — цифры и латинские буквы A–Z');
        }

        return new self($normalized);
    }

    public function getValue(): string
    {
        return $this->value;
    }
}
