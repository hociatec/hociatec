<?php

declare(strict_types=1);

namespace App\Module\Order\Entity;

use Doctrine\ORM\Mapping as ORM;

trait OrderInvoiceTrait
{
    #[ORM\Column(length: 30, nullable: true, unique: true)]
    private ?string $invoiceNumber = null;

    #[ORM\Column(length: 20, options: ['default' => 'issued'])]
    private string $invoiceStatus = 'issued';

    #[ORM\Column(type: 'datetime_immutable', nullable: true)]
    private ?\DateTimeImmutable $invoicedAt = null;

    #[ORM\Column(length: 180, nullable: true)]
    private ?string $billingName = null;

    #[ORM\Column(length: 180, nullable: true)]
    private ?string $billingCompany = null;

    #[ORM\Column(length: 20, nullable: true)]
    private ?string $billingCompanySiren = null;

    #[ORM\Column(length: 32, nullable: true)]
    private ?string $billingCompanyVatNumber = null;

    #[ORM\Column(length: 80, nullable: true)]
    private ?string $purchaseOrderNumber = null;

    #[ORM\Column(length: 180, nullable: true)]
    private ?string $billingEmail = null;

    #[ORM\Column(type: 'text', nullable: true)]
    private ?string $billingAddress = null;

    #[ORM\Column(length: 20, nullable: true)]
    private ?string $billingPostalCode = null;

    #[ORM\Column(length: 100, nullable: true)]
    private ?string $billingCity = null;

    #[ORM\Column(length: 3, options: ['default' => 'EUR'])]
    private string $currencyCode = 'EUR';

    #[ORM\Column(length: 40, options: ['default' => 'UBL-2.1'])]
    private string $electronicFormat = 'UBL-2.1';

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $invoicePdfPath = null;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $invoiceXmlPath = null;

    #[ORM\Column(type: 'datetime_immutable', nullable: true)]
    private ?\DateTimeImmutable $orderCreatedEmailSentAt = null;

    #[ORM\Column(type: 'datetime_immutable', nullable: true)]
    private ?\DateTimeImmutable $invoiceEmailSentAt = null;

    #[ORM\Column(type: 'datetime_immutable', nullable: true)]
    private ?\DateTimeImmutable $statusConfirmedEmailSentAt = null;

    #[ORM\Column(type: 'datetime_immutable', nullable: true)]
    private ?\DateTimeImmutable $statusDeliveredEmailSentAt = null;

    #[ORM\Column(type: 'datetime_immutable', nullable: true)]
    private ?\DateTimeImmutable $statusCancelledEmailSentAt = null;

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

    public function getBillingName(): ?string
    {
        return $this->billingName;
    }

    public function setBillingName(?string $billingName): self
    {
        $this->billingName = $billingName;

        return $this;
    }

    public function getBillingCompany(): ?string
    {
        return $this->billingCompany;
    }

    public function setBillingCompany(?string $billingCompany): self
    {
        $this->billingCompany = $billingCompany;

        return $this;
    }

    public function getBillingCompanySiren(): ?string
    {
        return $this->billingCompanySiren;
    }

    public function setBillingCompanySiren(?string $billingCompanySiren): self
    {
        $this->billingCompanySiren = $billingCompanySiren;

        return $this;
    }

    public function getBillingCompanyVatNumber(): ?string
    {
        return $this->billingCompanyVatNumber;
    }

    public function setBillingCompanyVatNumber(?string $billingCompanyVatNumber): self
    {
        $this->billingCompanyVatNumber = $billingCompanyVatNumber;

        return $this;
    }

    public function getPurchaseOrderNumber(): ?string
    {
        return $this->purchaseOrderNumber;
    }

    public function setPurchaseOrderNumber(?string $purchaseOrderNumber): self
    {
        $this->purchaseOrderNumber = $purchaseOrderNumber;

        return $this;
    }

    public function getBillingEmail(): ?string
    {
        return $this->billingEmail;
    }

    public function setBillingEmail(?string $billingEmail): self
    {
        $this->billingEmail = $billingEmail;

        return $this;
    }

    public function getBillingAddress(): ?string
    {
        return $this->billingAddress;
    }

    public function setBillingAddress(?string $billingAddress): self
    {
        $this->billingAddress = $billingAddress;

        return $this;
    }

    public function getBillingPostalCode(): ?string
    {
        return $this->billingPostalCode;
    }

    public function setBillingPostalCode(?string $billingPostalCode): self
    {
        $this->billingPostalCode = $billingPostalCode;

        return $this;
    }

    public function getBillingCity(): ?string
    {
        return $this->billingCity;
    }

    public function setBillingCity(?string $billingCity): self
    {
        $this->billingCity = $billingCity;

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

    public function getInvoicePdfPath(): ?string
    {
        return $this->invoicePdfPath;
    }

    public function setInvoicePdfPath(?string $invoicePdfPath): self
    {
        $this->invoicePdfPath = $invoicePdfPath;

        return $this;
    }

    public function getInvoiceXmlPath(): ?string
    {
        return $this->invoiceXmlPath;
    }

    public function setInvoiceXmlPath(?string $invoiceXmlPath): self
    {
        $this->invoiceXmlPath = $invoiceXmlPath;

        return $this;
    }

    public function getOrderCreatedEmailSentAt(): ?\DateTimeImmutable
    {
        return $this->orderCreatedEmailSentAt;
    }

    public function setOrderCreatedEmailSentAt(?\DateTimeImmutable $sentAt): self
    {
        $this->orderCreatedEmailSentAt = $sentAt;

        return $this;
    }

    public function getInvoiceEmailSentAt(): ?\DateTimeImmutable
    {
        return $this->invoiceEmailSentAt;
    }

    public function setInvoiceEmailSentAt(?\DateTimeImmutable $sentAt): self
    {
        $this->invoiceEmailSentAt = $sentAt;

        return $this;
    }

    public function getStatusConfirmedEmailSentAt(): ?\DateTimeImmutable
    {
        return $this->statusConfirmedEmailSentAt;
    }

    public function setStatusConfirmedEmailSentAt(?\DateTimeImmutable $sentAt): self
    {
        $this->statusConfirmedEmailSentAt = $sentAt;

        return $this;
    }

    public function getStatusDeliveredEmailSentAt(): ?\DateTimeImmutable
    {
        return $this->statusDeliveredEmailSentAt;
    }

    public function setStatusDeliveredEmailSentAt(?\DateTimeImmutable $sentAt): self
    {
        $this->statusDeliveredEmailSentAt = $sentAt;

        return $this;
    }

    public function getStatusCancelledEmailSentAt(): ?\DateTimeImmutable
    {
        return $this->statusCancelledEmailSentAt;
    }

    public function setStatusCancelledEmailSentAt(?\DateTimeImmutable $sentAt): self
    {
        $this->statusCancelledEmailSentAt = $sentAt;

        return $this;
    }
}
