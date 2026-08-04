<?php

declare(strict_types=1);

namespace App\Module\Voucher\Application\Mapper;

use App\Module\Voucher\Application\Port\VoucherRepositoryPort;
use App\Module\Voucher\Domain\Entity\Voucher;

final readonly class VoucherPayload
{
    public function __construct(private VoucherRepositoryPort $vouchers)
    {
    }

    /**
     * @param array{
     *   name:string,
     *   code:string,
     *   description?:?string,
     *   discountType:string,
     *   discountValue:int,
     *   isActive?:bool,
     *   startsAt?:?\DateTimeImmutable,
     *   endsAt?:?\DateTimeImmutable
     * } $data
     *
     * @return array{name:string, code:string, description:?string, discountType:string, discountValue:int, isActive:bool, startsAt:?\DateTimeImmutable, endsAt:?\DateTimeImmutable}
     */
    public function normalize(array $data, ?Voucher $current = null): array
    {
        $name = trim($data['name']);
        $code = Voucher::normalizeCode($data['code']);
        $discountType = trim($data['discountType']);
        $discountValue = $data['discountValue'];
        $description = array_key_exists('description', $data) ? $this->normalizeOptionalString($data['description']) : $current?->getDescription();
        $isActive = array_key_exists('isActive', $data) ? (bool) $data['isActive'] : ($current?->isActive() ?? true);
        $startsAt = array_key_exists('startsAt', $data) ? $data['startsAt'] : $current?->getStartsAt();
        $endsAt = array_key_exists('endsAt', $data) ? $data['endsAt'] : $current?->getEndsAt();

        $this->assertValidData($name, $code, $discountType, $discountValue, $startsAt, $endsAt, $current);

        return [
            'name' => $name,
            'code' => $code,
            'description' => $description,
            'discountType' => $discountType,
            'discountValue' => $discountValue,
            'isActive' => $isActive,
            'startsAt' => $startsAt,
            'endsAt' => $endsAt,
        ];
    }

    /**
     * @param array{name:string, code:string, description:?string, discountType:string, discountValue:int, isActive:bool, startsAt:?\DateTimeImmutable, endsAt:?\DateTimeImmutable} $payload
     */
    public function apply(Voucher $voucher, array $payload): void
    {
        $voucher
            ->setName($payload['name'])
            ->setCode($payload['code'])
            ->setDescription($payload['description'])
            ->changeDiscount($payload['discountType'], $payload['discountValue'])
            ->setIsActive($payload['isActive'])
            ->setStartsAt($payload['startsAt'])
            ->setEndsAt($payload['endsAt']);
    }

    private function assertValidData(
        string $name,
        string $code,
        string $discountType,
        int $discountValue,
        ?\DateTimeImmutable $startsAt,
        ?\DateTimeImmutable $endsAt,
        ?Voucher $current,
    ): void {
        if ('' === $name || '' === $code || '' === $discountType) {
            throw new \InvalidArgumentException('Champs obligatoires manquants.');
        }

        if (!\in_array($discountType, [Voucher::TYPE_PERCENT, Voucher::TYPE_FIXED_CENTS], true)) {
            throw new \InvalidArgumentException('Type de remise invalide.');
        }

        if ($discountValue <= 0) {
            throw new \InvalidArgumentException('La valeur de remise doit être supérieure à zéro.');
        }

        if (Voucher::TYPE_PERCENT === $discountType && $discountValue > 100) {
            throw new \InvalidArgumentException('La remise en pourcentage ne peut pas dépasser 100 %.');
        }

        if (null !== $startsAt && null !== $endsAt && $startsAt >= $endsAt) {
            throw new \InvalidArgumentException('La date de fin doit être postérieure à la date de début.');
        }

        $existing = $this->vouchers->findOneByCode($code);
        if (null !== $existing && (null === $current || $existing->getId() !== $current->getId())) {
            throw new \InvalidArgumentException('Ce code existe déjà.');
        }
    }

    private function normalizeOptionalString(mixed $value): ?string
    {
        if (null === $value) {
            return null;
        }

        $normalized = trim((string) $value);

        return '' === $normalized ? null : $normalized;
    }
}
