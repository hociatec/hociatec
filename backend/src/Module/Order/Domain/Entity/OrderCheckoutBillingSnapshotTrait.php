<?php

declare(strict_types=1);

namespace App\Module\Order\Domain\Entity;

trait OrderCheckoutBillingSnapshotTrait
{
    public function getBillingName(): ?string
    {
        return $this->billingSnapshot()->name();
    }

    public function setBillingName(?string $billingName): self
    {
        $this->billingName = $billingName;

        return $this;
    }

    public function getBillingCompany(): ?string
    {
        return $this->billingSnapshot()->company();
    }

    public function setBillingCompany(?string $billingCompany): self
    {
        $this->billingCompany = $billingCompany;

        return $this;
    }

    public function getBillingCompanySiren(): ?string
    {
        return $this->billingSnapshot()->companySiren();
    }

    public function setBillingCompanySiren(?string $billingCompanySiren): self
    {
        $this->billingCompanySiren = $billingCompanySiren;

        return $this;
    }

    public function getBillingCompanyVatNumber(): ?string
    {
        return $this->billingSnapshot()->companyVatNumber();
    }

    public function setBillingCompanyVatNumber(?string $billingCompanyVatNumber): self
    {
        $this->billingCompanyVatNumber = $billingCompanyVatNumber;

        return $this;
    }

    public function getPurchaseOrderNumber(): ?string
    {
        return $this->billingSnapshot()->purchaseOrderNumber();
    }

    public function setPurchaseOrderNumber(?string $purchaseOrderNumber): self
    {
        $this->purchaseOrderNumber = $purchaseOrderNumber;

        return $this;
    }

    public function getBillingEmail(): ?string
    {
        return $this->billingSnapshot()->email();
    }

    public function setBillingEmail(?string $billingEmail): self
    {
        $this->billingEmail = $billingEmail;

        return $this;
    }

    public function getBillingAddress(): ?string
    {
        return $this->billingSnapshot()->address();
    }

    public function setBillingAddress(?string $billingAddress): self
    {
        $this->billingAddress = $billingAddress;

        return $this;
    }

    public function getBillingPostalCode(): ?string
    {
        return $this->billingSnapshot()->postalCode();
    }

    public function setBillingPostalCode(?string $billingPostalCode): self
    {
        $this->billingPostalCode = $billingPostalCode;

        return $this;
    }

    public function getBillingCity(): ?string
    {
        return $this->billingSnapshot()->city();
    }

    public function setBillingCity(?string $billingCity): self
    {
        $this->billingCity = $billingCity;

        return $this;
    }
}
