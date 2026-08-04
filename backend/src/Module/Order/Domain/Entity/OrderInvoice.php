<?php

declare(strict_types=1);

namespace App\Module\Order\Domain\Entity;

use Doctrine\ORM\Mapping as ORM;

#[ORM\Embeddable]
final class OrderInvoice
{
    #[ORM\Column(name: 'invoice_number', length: 30, nullable: true, unique: true)]
    private ?string $number = null;

    #[ORM\Column(name: 'invoice_status', length: 20, options: ['default' => Order::INVOICE_STATUS_ISSUED])]
    private string $status = Order::INVOICE_STATUS_ISSUED;

    #[ORM\Column(name: 'invoiced_at', type: 'datetime_immutable', nullable: true)]
    private ?\DateTimeImmutable $invoicedAt = null;

    #[ORM\Column(length: 3, options: ['default' => 'EUR'])]
    private string $currencyCode = 'EUR';

    #[ORM\Column(length: 40, options: ['default' => 'UBL-2.1'])]
    private string $electronicFormat = 'UBL-2.1';

    #[ORM\Column(name: 'invoice_pdf_path', length: 255, nullable: true)]
    private ?string $pdfPath = null;

    #[ORM\Column(name: 'invoice_xml_path', length: 255, nullable: true)]
    private ?string $xmlPath = null;

    public function getNumber(): ?string
    {
        return $this->number;
    }

    public function setNumber(?string $number): self
    {
        $this->number = $number;

        return $this;
    }

    public function getStatus(): string
    {
        return $this->status;
    }

    public function setStatus(string $status): self
    {
        $this->status = $status;

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

    public function getPdfPath(): ?string
    {
        return $this->pdfPath;
    }

    public function setPdfPath(?string $pdfPath): self
    {
        $this->pdfPath = $pdfPath;

        return $this;
    }

    public function getXmlPath(): ?string
    {
        return $this->xmlPath;
    }

    public function setXmlPath(?string $xmlPath): self
    {
        $this->xmlPath = $xmlPath;

        return $this;
    }
}
