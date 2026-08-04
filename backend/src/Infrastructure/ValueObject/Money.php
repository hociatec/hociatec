<?php

declare(strict_types=1);

namespace App\Infrastructure\ValueObject;

final readonly class Money
{
    private function __construct(private int $cents)
    {
        if ($cents < 0) {
            throw new \InvalidArgumentException('Un montant ne peut pas être négatif.');
        }
    }

    public static function fromCents(int $cents): self
    {
        return new self($cents);
    }

    public function cents(): int
    {
        return $this->cents;
    }

    public function add(self $other): self
    {
        return self::fromCents($this->cents + $other->cents);
    }

    public function subtract(self $other): self
    {
        return self::fromCents(max(0, $this->cents - $other->cents));
    }

    public function equals(self $other): bool
    {
        return $this->cents === $other->cents;
    }
}
