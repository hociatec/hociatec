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

    /**
     * @param array{
     *   name?: string,
     *   code?: ?string,
     *   description?: ?string,
     *   discountType?: string,
     *   discountValue?: int,
     *   isActive?: bool,
     *   startsAt?: ?string,
     *   endsAt?: ?string,
     *   sendEmail?: bool
     * }|null $payload
     */
    public function __construct(?array $payload = null)
    {
        $data = array_replace([
            'name' => '',
            'code' => null,
            'description' => null,
            'discountType' => Voucher::TYPE_FIXED_CENTS,
            'discountValue' => 0,
            'isActive' => true,
            'startsAt' => null,
            'endsAt' => null,
            'sendEmail' => true,
        ], $payload ?? []);
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
        return new self([
            'name' => is_string($p['name'] ?? null) ? trim($p['name']) : '',
            'code' => is_string($p['code'] ?? null) ? mb_strtoupper(trim($p['code'])) : null,
            'description' => is_string($p['description'] ?? null) ? trim($p['description']) : null,
            'discountType' => is_string($p['discountType'] ?? null) ? trim($p['discountType']) : Voucher::TYPE_FIXED_CENTS,
            'discountValue' => is_numeric($p['discountValue'] ?? null) ? (int) $p['discountValue'] : 0,
            'isActive' => is_bool($p['isActive'] ?? null) ? $p['isActive'] : true,
            'startsAt' => is_string($p['startsAt'] ?? null) ? trim($p['startsAt']) : null,
            'endsAt' => is_string($p['endsAt'] ?? null) ? trim($p['endsAt']) : null,
            'sendEmail' => is_bool($p['sendEmail'] ?? null) ? $p['sendEmail'] : true,
        ]);
    }
}
