<?php

declare(strict_types=1);

namespace App\Module\Order\Domain\Entity;

use Doctrine\ORM\Mapping as ORM;

trait OrderInvoiceStateTrait
{
    #[ORM\Column(length: 30, nullable: true, unique: true)]
    private ?string $invoiceNumber = null;

    #[ORM\Column(length: 20, options: ['default' => 'issued'])]
    private string $invoiceStatus = 'issued';

    #[ORM\Column(type: 'datetime_immutable', nullable: true)]
    private ?\DateTimeImmutable $invoicedAt = null;

    #[ORM\Column(length: 3, options: ['default' => 'EUR'])]
    private string $currencyCode = 'EUR';

    #[ORM\Column(length: 40, options: ['default' => 'UBL-2.1'])]
    private string $electronicFormat = 'UBL-2.1';

    public function getInvoiceNumber(): ?string
    {
        return $this->invoiceNumber;
    }

    public function setInvoiceNumber(?string $invoiceNumber): self
    {
        $this->invoiceNumber = $invoiceNumber;

        return $this;
    }

    public function getInvoiceStatus(): string
    {
        return $this->invoiceStatus;
    }

    public function setInvoiceStatus(string $invoiceStatus): self
    {
        $this->invoiceStatus = $invoiceStatus;

        return $this;
    }

    public function getInvoicedAt(): ?\DateTimeImmutable
    {
        return $this->invoicedAt;
    }

    public function setInvoicedAt(?\DateTimeImmutable $invoicedAt): self
    {
        $this->invoicedAt = $invoicedAt;

        return $this;
    }

    public function getCurrencyCode(): string
    {
        return $this->currencyCode;
    }

    public function setCurrencyCode(string $currencyCode): self
    {
        $this->currencyCode = $currencyCode;

        return $this;
    }

    public function getElectronicFormat(): string
    {
        return $this->electronicFormat;
    }

    public function setElectronicFormat(string $electronicFormat): self
    {
        $this->electronicFormat = $electronicFormat;

        return $this;
    }
}
