<?php

declare(strict_types=1);

namespace App\Module\Voucher\Application\Handler;

use App\Module\Voucher\Application\Mapper\VoucherPayload;
use App\Module\Voucher\Domain\Entity\Voucher;
use App\Shared\Application\UnitOfWork;
use Doctrine\DBAL\Exception\UniqueConstraintViolationException;

final readonly class CreateVoucherHandler
{
    public function __construct(
        private UnitOfWork $persistence,
        private VoucherPayload $payload,
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
        $payload = $this->payload->normalize($data);
        $voucher = new Voucher($payload['name'], $payload['code'], $payload['discountType'], $payload['discountValue']);
        $this->payload->apply($voucher, $payload);

        $this->persistence->persist($voucher);
        $this->flushWithDuplicateCodeHandling();

        return $voucher;
    }

    private function flushWithDuplicateCodeHandling(): void
    {
        try {
            $this->persistence->commit();
        } catch (UniqueConstraintViolationException $exception) {
            throw new \InvalidArgumentException('Ce code existe déjà.', previous: $exception);
        }
    }
}
