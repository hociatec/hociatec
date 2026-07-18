<?php

declare(strict_types=1);

namespace App\Module\Voucher\Service;

use App\Module\Voucher\Entity\Voucher;
use App\Module\Voucher\Repository\VoucherRepository;
use Doctrine\ORM\EntityManagerInterface;

final class VoucherManager
{
    public function __construct(
        private readonly VoucherRepository $vouchers,
        private readonly EntityManagerInterface $entityManager,
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
        $discountValue = (int) $data['discountValue'];

        $this->assertValidData($name, $code, $discountType, $discountValue, null);

        $voucher = new Voucher($name, $code, $discountType, $discountValue);
        $voucher
            ->setDescription(isset($data['description']) ? trim((string) $data['description']) : null)
            ->setIsActive((bool) ($data['isActive'] ?? true))
            ->setStartsAt($data['startsAt'] ?? null)
            ->setEndsAt($data['endsAt'] ?? null);

        $this->entityManager->persist($voucher);
        $this->entityManager->flush();

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
        $discountValue = (int) $data['discountValue'];

        $this->assertValidData($name, $code, $discountType, $discountValue, $voucher);

        $voucher
            ->setName($name)
            ->setCode($code)
            ->setDescription(isset($data['description']) ? trim((string) $data['description']) : null)
            ->setDiscountType($discountType)
            ->setDiscountValue($discountValue)
            ->setIsActive((bool) ($data['isActive'] ?? true))
            ->setStartsAt($data['startsAt'] ?? null)
            ->setEndsAt($data['endsAt'] ?? null);

        $this->entityManager->persist($voucher);
        $this->entityManager->flush();

        return $voucher;
    }

    private function normalizeCode(?string $value): string
    {
        return mb_strtoupper(trim((string) $value));
    }

    private function assertValidData(string $name, string $code, string $discountType, int $discountValue, ?Voucher $current): void
    {
        if ($name === '' || $code === '' || $discountType === '') {
            throw new \InvalidArgumentException('Champs obligatoires manquants.');
        }

        if (!\in_array($discountType, [Voucher::TYPE_PERCENT, Voucher::TYPE_FIXED_CENTS], true)) {
            throw new \InvalidArgumentException('Type de remise invalide.');
        }

        if ($discountValue <= 0) {
            throw new \InvalidArgumentException('La valeur de remise doit être supérieure à zéro.');
        }

        $existing = $this->vouchers->findOneByCode($code);
        if ($existing !== null && ($current === null || $existing->getId() !== $current->getId())) {
            throw new \InvalidArgumentException('Ce code existe déjà.');
        }
    }
}
