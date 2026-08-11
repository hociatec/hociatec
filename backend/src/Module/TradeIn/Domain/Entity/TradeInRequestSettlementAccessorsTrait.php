<?php

declare(strict_types=1);

namespace App\Module\TradeIn\Domain\Entity;

trait TradeInRequestSettlementAccessorsTrait
{
    public function getPaymentMethod(): ?string
    {
        return $this->paymentMethod;
    }

    public function getPaymentStatus(): string
    {
        return $this->paymentStatus;
    }

    public function getTransactionReference(): ?string
    {
        return $this->transactionReference;
    }

    public function getPaidAt(): ?\DateTimeImmutable
    {
        return $this->paidAt;
    }

    public function getRibPath(): ?string
    {
        return $this->ribPath;
    }

    public function getRibOriginalName(): ?string
    {
        return $this->ribOriginalName;
    }

    public function getRibSize(): ?int
    {
        return $this->ribSize;
    }

    public function getReceiptPath(): ?string
    {
        return $this->receiptPath;
    }

    public function getVoucherCode(): ?string
    {
        return $this->voucherCode;
    }
}
