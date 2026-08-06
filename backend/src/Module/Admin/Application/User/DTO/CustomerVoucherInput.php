<?php

declare(strict_types=1);

namespace App\Module\Admin\Application\User\DTO;

use App\Module\Voucher\Domain\Entity\Voucher;
use Symfony\Component\Validator\Constraints as Assert;

final readonly class CustomerVoucherInput
{
    #[Assert\NotBlank]
    public string $name;
    public ?string $code;
    public ?string $description;
    #[Assert\Choice(choices: [Voucher::TYPE_FIXED_CENTS, Voucher::TYPE_PERCENT])]
    public string $discountType;
    #[Assert\Positive]
    public int $discountValue;
    public bool $isActive;
    public ?string $startsAt;
    public ?string $endsAt;
    public bool $sendEmail;

    public function __construct(mixed ...$values)
    {
        $data = $this->mapValues($values);
        $this->name = (string) $data['name'];
        $this->code = $data['code'];
        $this->description = $data['description'];
        $this->discountType = (string) $data['discountType'];
        $this->discountValue = (int) $data['discountValue'];
        $this->isActive = (bool) $data['isActive'];
        $this->startsAt = $data['startsAt'];
        $this->endsAt = $data['endsAt'];
        $this->sendEmail = (bool) $data['sendEmail'];
    }

    /** @param array<string,mixed> $p */
    public static function fromArray(array $p): self
    {
        return new self(is_string($p['name'] ?? null) ? trim($p['name']) : '', is_string($p['code'] ?? null) ? mb_strtoupper(trim($p['code'])) : null, is_string($p['description'] ?? null) ? trim($p['description']) : null, is_string($p['discountType'] ?? null) ? trim($p['discountType']) : Voucher::TYPE_FIXED_CENTS, is_numeric($p['discountValue'] ?? null) ? (int) $p['discountValue'] : 0, is_bool($p['isActive'] ?? null) ? $p['isActive'] : true, is_string($p['startsAt'] ?? null) ? trim($p['startsAt']) : null, is_string($p['endsAt'] ?? null) ? trim($p['endsAt']) : null, is_bool($p['sendEmail'] ?? null) ? $p['sendEmail'] : true);
    }

    /**
     * @param array<int|string, mixed> $values
     *
     * @return array<string, mixed>
     */
    private function mapValues(array $values): array
    {
        $keys = ['name', 'code', 'description', 'discountType', 'discountValue', 'isActive', 'startsAt', 'endsAt', 'sendEmail'];
        $defaults = array_fill_keys($keys, null);
        $defaults['name'] = '';
        $defaults['discountType'] = Voucher::TYPE_FIXED_CENTS;
        $defaults['discountValue'] = 0;
        $defaults['isActive'] = true;
        $defaults['sendEmail'] = true;
        foreach ($values as $index => $value) {
            if (!is_int($index)) {
                continue;
            }
            if (isset($keys[$index])) {
                $defaults[$keys[$index]] = $value;
            }
        }

        return array_replace($defaults, array_filter($values, 'is_string', ARRAY_FILTER_USE_KEY));
    }
}
