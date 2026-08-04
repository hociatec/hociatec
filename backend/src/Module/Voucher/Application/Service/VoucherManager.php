<?php

declare(strict_types=1);

namespace App\Module\Voucher\Application\Service;

use App\Infrastructure\Persistence\DoctrinePersistence;
use App\Module\Voucher\Domain\Entity\Voucher;
use App\Module\Voucher\Infrastructure\Repository\VoucherRepository;
use Doctrine\DBAL\Exception\UniqueConstraintViolationException;

final class VoucherManager
{
    public function __construct(
        private readonly VoucherRepository $vouchers,
        private readonly DoctrinePersistence $persistence,
    ) {
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
     */
    public function create(array $data): Voucher
    {
        $name = trim($data['name']);
        $code = $this->normalizeCode($data['code']);
        $discountType = trim($data['discountType']);
        $discountValue = $data['discountValue'];
        $description = $this->normalizeOptionalString($data['description'] ?? null);
        $startsAt = $data['startsAt'] ?? null;
        $endsAt = $data['endsAt'] ?? null;

        $this->assertValidData($name, $code, $discountType, $discountValue, $startsAt, $endsAt, null);

        $voucher = new Voucher($name, $code, $discountType, $discountValue);
        $voucher
            ->setDescription($description)
            ->setIsActive((bool) ($data['isActive'] ?? true))
            ->setStartsAt($startsAt)
            ->setEndsAt($endsAt);

        $this->persistence->persist($voucher);
        $this->flushWithDuplicateCodeHandling();

        return $voucher;
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
     */
    public function update(Voucher $voucher, array $data): Voucher
    {
        $name = trim($data['name']);
        $code = $this->normalizeCode($data['code']);
        $discountType = trim($data['discountType']);
        $discountValue = $data['discountValue'];
        $description = array_key_exists('description', $data)
            ? $this->normalizeOptionalString($data['description'])
            : $voucher->getDescription();
        $isActive = array_key_exists('isActive', $data)
            ? (bool) $data['isActive']
            : $voucher->isActive();
        $startsAt = array_key_exists('startsAt', $data)
            ? $data['startsAt']
            : $voucher->getStartsAt();
        $endsAt = array_key_exists('endsAt', $data)
            ? $data['endsAt']
            : $voucher->getEndsAt();

        $this->assertValidData($name, $code, $discountType, $discountValue, $startsAt, $endsAt, $voucher);

        $voucher
            ->setName($name)
            ->setCode($code)
            ->setDescription($description)
            ->changeDiscount($discountType, $discountValue)
            ->setIsActive($isActive)
            ->setStartsAt($startsAt)
            ->setEndsAt($endsAt);

        $this->flushWithDuplicateCodeHandling();

        return $voucher;
    }

    public function delete(Voucher $voucher): void
    {
        $this->persistence->remove($voucher);
        $this->persistence->flush();
    }

    private function normalizeCode(?string $value): string
    {
        return Voucher::normalizeCode($value);
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

    private function flushWithDuplicateCodeHandling(): void
    {
        try {
            $this->persistence->flush();
        } catch (UniqueConstraintViolationException $exception) {
            throw new \InvalidArgumentException('Ce code existe déjà.', previous: $exception);
        }
    }
}
