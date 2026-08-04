<?php

declare(strict_types=1);

namespace App\Module\Order\Domain\Entity;

trait OrderInvoiceStateTrait
{
    public function getInvoiceNumber(): ?string
    {
        return $this->invoice->getNumber();
    }

    public function setInvoiceNumber(?string $invoiceNumber): self
    {
        $this->invoice->setNumber($invoiceNumber);

        return $this;
    }

    public function getInvoiceStatus(): string
    {
        return $this->invoice->getStatus();
    }

    public function setInvoiceStatus(string $invoiceStatus): self
    {
        $this->invoice->setStatus($invoiceStatus);

        return $this;
    }

    public function getInvoicedAt(): ?\DateTimeImmutable
    {
        return $this->invoice->getInvoicedAt();
    }

    public function setInvoicedAt(?\DateTimeImmutable $invoicedAt): self
    {
        $this->invoice->setInvoicedAt($invoicedAt);

        return $this;
    }

    public function getCurrencyCode(): string
    {
        return $this->invoice->getCurrencyCode();
    }

    public function setCurrencyCode(string $currencyCode): self
    {
        $this->invoice->setCurrencyCode($currencyCode);

        return $this;
    }

    public function getElectronicFormat(): string
    {
        return $this->invoice->getElectronicFormat();
    }

    public function setElectronicFormat(string $electronicFormat): self
    {
        $this->invoice->setElectronicFormat($electronicFormat);

        return $this;
    }
}
