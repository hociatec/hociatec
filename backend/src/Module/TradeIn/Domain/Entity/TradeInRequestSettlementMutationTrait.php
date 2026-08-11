<?php

declare(strict_types=1);

namespace App\Module\TradeIn\Domain\Entity;

use App\Module\TradeIn\Domain\ValueObject\TradeInClosure;

trait TradeInRequestSettlementMutationTrait
{
    public function setClosure(int $finalOfferCents, string $paymentMethod, string $paymentStatus, ?string $transactionReference, ?\DateTimeImmutable $paidAt): self
    {
        $closure = TradeInClosure::fromInput($finalOfferCents, $paymentMethod, $paymentStatus, $transactionReference, $paidAt);
        $this->finalOfferCents = $closure->finalOfferCents;
        $this->paymentMethod = $closure->paymentMethod;
        $this->paymentStatus = $closure->paymentStatus;
        $this->transactionReference = $closure->transactionReference;
        $this->paidAt = $closure->paidAt;
        $this->closedAt = new \DateTimeImmutable();
        $this->touch();

        return $this;
    }

    public function setRib(string $path, string $originalName, int $size, string $sha256): self
    {
        $this->ribPath = $path;
        $this->ribOriginalName = $originalName;
        $this->ribSize = $size;
        $this->ribSha256 = $sha256;
        $this->touch();

        return $this;
    }

    public function setReceiptPath(?string $path): self
    {
        $this->receiptPath = $path;
        $this->touch();

        return $this;
    }

    public function setVoucherCode(?string $code): self
    {
        $this->voucherCode = null !== $code && '' !== trim($code) ? trim($code) : null;
        $this->touch();

        return $this;
    }
}
