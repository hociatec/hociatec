<?php

declare(strict_types=1);

namespace App\Module\Quote\Domain\Entity;

trait QuoteConversionTrait
{
    public function getConvertedOrderId(): ?int
    {
        return $this->convertedOrderId;
    }

    public function setConvertedOrderId(?int $convertedOrderId): self
    {
        $this->convertedOrderId = $convertedOrderId;

        return $this;
    }

    public function getConvertedOrderNumber(): ?string
    {
        return $this->convertedOrderNumber;
    }

    public function setConvertedOrderNumber(?string $convertedOrderNumber): self
    {
        $this->convertedOrderNumber = $convertedOrderNumber;

        return $this;
    }

    public function convertToOrder(int $orderId, string $orderNumber): self
    {
        if ($orderId <= 0) {
            throw new \InvalidArgumentException('Identifiant de commande invalide.');
        }

        $orderNumber = trim($orderNumber);
        if ('' === $orderNumber) {
            throw new \InvalidArgumentException('Numero de commande invalide.');
        }

        $this->convertedOrderId = $orderId;
        $this->convertedOrderNumber = $orderNumber;
        $this->accept();

        return $this;
    }
}
