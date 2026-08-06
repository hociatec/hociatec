<?php

declare(strict_types=1);

namespace App\Module\Order\Domain\Entity;

use App\Module\Order\Domain\Enum\InvoiceStatus;
use App\Shared\Domain\ValueObject\Currency;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Embeddable]
final class OrderInvoice
{
    #[ORM\Column(name: 'invoice_number', length: 30, nullable: true, unique: true)]
    private ?string $number = null;

    #[ORM\Column(name: 'invoice_status', length: 20, enumType: InvoiceStatus::class, options: ['default' => InvoiceStatus::ISSUED->value])]
    private InvoiceStatus $status = InvoiceStatus::ISSUED;

    #[ORM\Column(name: 'invoiced_at', type: 'datetime_immutable', nullable: true)]
    private ?\DateTimeImmutable $invoicedAt = null;

    #[ORM\Column(length: 3, enumType: Currency::class, options: ['default' => Currency::EUR->value])]
    private Currency $currencyCode = Currency::EUR;

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
        return $this->status->value;
    }

    public function setStatus(string $status): self
    {
        $this->status = InvoiceStatus::from($status);

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
        return $this->currencyCode->value;
    }

    public function setCurrencyCode(string $currencyCode): self
    {
        $this->currencyCode = Currency::fromCode($currencyCode);

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
