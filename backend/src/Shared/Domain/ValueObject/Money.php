<?php

declare(strict_types=1);

namespace App\Shared\Domain\ValueObject;

final readonly class Money
{
    private function __construct(private int $cents, private Currency $currency = Currency::EUR)
    {
        if ($cents < 0) {
            throw new \InvalidArgumentException('Un montant ne peut pas être négatif.');
        }
    }

    public static function fromCents(int $cents, Currency|string $currency = Currency::EUR): self
    {
        return new self($cents, $currency instanceof Currency ? $currency : Currency::fromCode($currency));
    }

    public function cents(): int
    {
        return $this->cents;
    }

    public function currency(): Currency
    {
        return $this->currency;
    }

    public function currencyCode(): string
    {
        return $this->currency->value;
    }

    public function add(self $other): self
    {
        $this->assertSameCurrency($other);

        return self::fromCents($this->cents + $other->cents, $this->currency);
    }

    public function subtract(self $other): self
    {
        $this->assertSameCurrency($other);

        return self::fromCents(max(0, $this->cents - $other->cents), $this->currency);
    }

    public function equals(self $other): bool
    {
        return $this->cents === $other->cents && $this->currency === $other->currency;
    }

    private function assertSameCurrency(self $other): void
    {
        if ($this->currency !== $other->currency) {
            throw new \InvalidArgumentException('Les montants doivent utiliser la même monnaie.');
        }
    }
}
