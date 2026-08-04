<?php

declare(strict_types=1);

namespace App\Module\Order\Domain\Entity;

use Doctrine\ORM\Mapping as ORM;

trait OrderCheckoutCustomerTrait
{
    #[ORM\Column(length: 180, nullable: true)]
    private ?string $customerFullName = null;

    #[ORM\Column(length: 180)]
    private string $customerEmail;

    #[ORM\Column(length: 180, nullable: true)]
    private ?string $shippingName = null;

    #[ORM\Column(type: 'text', nullable: true)]
    private ?string $shippingAddress = null;

    #[ORM\Column(length: 20, nullable: true)]
    private ?string $shippingPostalCode = null;

    #[ORM\Column(length: 100, nullable: true)]
    private ?string $shippingCity = null;

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

    public function getCustomerFullName(): ?string
    {
        return $this->customerFullName;
    }

    public function setCustomerFullName(?string $customerFullName): self
    {
        $this->customerFullName = $customerFullName;

        return $this;
    }

    public function getCustomerEmail(): string
    {
        return $this->customerEmail;
    }

    public function setCustomerEmail(string $customerEmail): self
    {
        $this->customerEmail = $customerEmail;

        return $this;
    }

    public function getShippingName(): ?string
    {
        return $this->shippingName;
    }

    public function setShippingName(?string $shippingName): self
    {
        $this->shippingName = $shippingName;

        return $this;
    }

    public function getShippingAddress(): ?string
    {
        return $this->shippingAddress;
    }

    public function setShippingAddress(?string $shippingAddress): self
    {
        $this->shippingAddress = $shippingAddress;

        return $this;
    }

    public function getShippingPostalCode(): ?string
    {
        return $this->shippingPostalCode;
    }

    public function setShippingPostalCode(?string $shippingPostalCode): self
    {
        $this->shippingPostalCode = $shippingPostalCode;

        return $this;
    }

    public function getShippingCity(): ?string
    {
        return $this->shippingCity;
    }

    public function setShippingCity(?string $shippingCity): self
    {
        $this->shippingCity = $shippingCity;

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
}
