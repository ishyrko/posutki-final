<?php

declare(strict_types=1);

namespace App\Domain\Shared\ValueObject;

final readonly class Slug
{
    private function __construct(
        private string $value
    ) {
        $this->validate();
    }

    public static function fromString(string $text): self
    {
        return new self($text);
    }

    public function getValue(): string
    {
        return $this->value;
    }

    private function validate(): void
    {
        if (empty($this->value)) {
            throw new \InvalidArgumentException('Слаг не может быть пустым');
        }

        if (!preg_match('/^[a-z0-9]+(?:-[a-z0-9]+)*$/', $this->value)) {
            throw new \InvalidArgumentException('Неверный формат слага: только строчные буквы, цифры и дефисы');
        }
    }

    public function __toString(): string
    {
        return $this->value;
    }
}
