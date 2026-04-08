<?php

declare(strict_types=1);

namespace App\Domain\Shared\ValueObject;

final readonly class Id
{
    private function __construct(
        private int $value
    ) {
    }

    public static function fromInt(int $id): self
    {
        if ($id <= 0) {
            throw new \InvalidArgumentException('ID должен быть положительным целым числом');
        }

        return new self($id);
    }

    public static function fromString(string $id): self
    {
        if (!ctype_digit($id)) {
            throw new \InvalidArgumentException('Неверный формат ID');
        }

        return self::fromInt((int) $id);
    }

    public function getValue(): int
    {
        return $this->value;
    }

    public function equals(self $other): bool
    {
        return $this->value === $other->value;
    }

    public function __toString(): string
    {
        return (string) $this->value;
    }
}
