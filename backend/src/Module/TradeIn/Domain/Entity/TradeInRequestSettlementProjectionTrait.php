<?php

declare(strict_types=1);

namespace App\Module\TradeIn\Domain\Entity;

use App\Module\TradeIn\Domain\ValueObject\TradeInClosure;
use App\Module\TradeIn\Domain\ValueObject\TradeInPrivateDocument;

trait TradeInRequestSettlementProjectionTrait
{
    public function closure(): ?TradeInClosure
    {
        if (null === $this->finalOfferCents || null === $this->paymentMethod) {
            return null;
        }

        return new TradeInClosure($this->finalOfferCents, $this->paymentMethod, $this->paymentStatus, $this->transactionReference, $this->paidAt);
    }

    public function ribDocument(): ?TradeInPrivateDocument
    {
        if (null === $this->ribPath) {
            return null;
        }

        return new TradeInPrivateDocument($this->ribPath, $this->ribOriginalName, $this->ribSize, $this->ribSha256);
    }

    public function receiptDocument(): ?TradeInPrivateDocument
    {
        if (null === $this->receiptPath) {
            return null;
        }

        return new TradeInPrivateDocument($this->receiptPath);
    }
}
