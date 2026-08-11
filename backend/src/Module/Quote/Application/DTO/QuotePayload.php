<?php

declare(strict_types=1);

namespace App\Module\Quote\Application\DTO;

use App\Shared\Domain\ValueObject\Money;
use Symfony\Component\Validator\Constraints as Assert;
use Symfony\Component\Validator\Context\ExecutionContextInterface;

#[Assert\Callback('validateCustomer')]
#[Assert\Callback('validateItems')]
#[Assert\Callback('validateDates')]
final readonly class QuotePayload
{
    /** @var array<string,mixed> */
    public array $customer;
    public string $status;
    public Money $discount;
    public Money $shipping;
    public ?string $conditions;
    public ?string $validFrom;
    public ?string $validUntil;
    /** @var list<QuoteItemPayload> */
    public array $items;

    /**
     * @param array{
     *   customer?: array<string,mixed>,
     *   status?: string,
     *   discount?: Money,
     *   shipping?: Money,
     *   conditions?: ?string,
     *   validFrom?: ?string,
     *   validUntil?: ?string,
     *   items?: list<QuoteItemPayload>
     * }|null $payload
     */
    public function __construct(?array $payload = null)
    {
        $data = array_replace([
            'customer' => [],
            'status' => 'draft',
            'discount' => Money::fromCents(0),
            'shipping' => Money::fromCents(0),
            'conditions' => null,
            'validFrom' => null,
            'validUntil' => null,
            'items' => [],
        ], $payload ?? []);
        $this->customer = $data['customer'];
        $this->status = (string) $data['status'];
        $this->discount = $data['discount'];
        $this->shipping = $data['shipping'];
        $this->conditions = $data['conditions'];
        $this->validFrom = $data['validFrom'];
        $this->validUntil = $data['validUntil'];
        $this->items = $data['items'];
    }

    /** @param array<string,mixed> $payload */
    public static function fromArray(array $payload): self
    {
        return new self([
            'customer' => is_array($payload['customer'] ?? null) ? $payload['customer'] : [],
            'status' => is_string($payload['status'] ?? null) ? trim($payload['status']) : 'draft',
            'discount' => Money::fromCents(max(0, is_numeric($payload['discountCents'] ?? null) ? (int) $payload['discountCents'] : 0)),
            'shipping' => Money::fromCents(max(0, is_numeric($payload['shippingCents'] ?? null) ? (int) $payload['shippingCents'] : 0)),
            'conditions' => is_string($payload['conditions'] ?? null) ? trim($payload['conditions']) : null,
            'validFrom' => is_string($payload['validFrom'] ?? null) ? trim($payload['validFrom']) : null,
            'validUntil' => is_string($payload['validUntil'] ?? null) ? trim($payload['validUntil']) : null,
            'items' => is_array($payload['items'] ?? null)
                ? array_values(array_map(
                    static fn (mixed $item): QuoteItemPayload => QuoteItemPayload::fromArray(is_array($item) ? $item : []),
                    $payload['items'],
                ))
                : [],
        ]);
    }

    public function validateCustomer(ExecutionContextInterface $context): void
    {
        $name = $this->normalizedCustomerString('name');
        $email = $this->normalizedCustomerString('email');

        if (null === $name || mb_strlen($name) > 150) {
            $context->buildViolation('Le nom du client est obligatoire et doit contenir au maximum 150 caracteres.')
                ->atPath('customer.name')
                ->addViolation();
        }

        if (null === $email || mb_strlen($email) > 180 || false === filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $context->buildViolation("L'adresse e-mail du client est invalide.")
                ->atPath('customer.email')
                ->addViolation();
        }
    }

    public function validateItems(ExecutionContextInterface $context): void
    {
        if ([] === $this->items) {
            $context->buildViolation('Le devis doit contenir au moins une ligne.')
                ->atPath('items')
                ->addViolation();

            return;
        }

        if (count($this->items) > 50) {
            $context->buildViolation('Le devis ne peut pas contenir plus de 50 lignes.')
                ->atPath('items')
                ->addViolation();
        }
    }

    public function validateDates(ExecutionContextInterface $context): void
    {
        foreach (['validFrom' => $this->validFrom, 'validUntil' => $this->validUntil] as $field => $value) {
            if (null === $value || '' === trim($value)) {
                continue;
            }

            if (false === preg_match('/^\d{4}-\d{2}-\d{2}$/', $value)) {
                $context->buildViolation('Le format de date attendu est YYYY-MM-DD.')
                    ->atPath($field)
                    ->addViolation();
            }
        }
    }

    private function normalizedCustomerString(string $key): ?string
    {
        $value = $this->customer[$key] ?? null;
        if (!is_scalar($value)) {
            return null;
        }

        $normalized = trim((string) $value);

        return '' === $normalized ? null : $normalized;
    }
}
