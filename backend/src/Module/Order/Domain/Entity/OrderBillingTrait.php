<?php

declare(strict_types=1);

namespace App\Module\Order\Domain\Entity;

trait OrderBillingTrait
{
    public function getBillingName(): ?string
    {
        return $this->billing->getBillingName();
    }

    public function setBillingName(?string $billingName): self
    {
        $this->billing->setBillingName($billingName);

        return $this;
    }

    public function getBillingCompany(): ?string
    {
        return $this->billing->getBillingCompany();
    }

    public function setBillingCompany(?string $billingCompany): self
    {
        $this->billing->setBillingCompany($billingCompany);

        return $this;
    }

    public function getBillingCompanySiren(): ?string
    {
        return $this->billing->getBillingCompanySiren();
    }

    public function setBillingCompanySiren(?string $billingCompanySiren): self
    {
        $this->billing->setBillingCompanySiren($billingCompanySiren);

        return $this;
    }

    public function getBillingCompanyVatNumber(): ?string
    {
        return $this->billing->getBillingCompanyVatNumber();
    }

    public function setBillingCompanyVatNumber(?string $billingCompanyVatNumber): self
    {
        $this->billing->setBillingCompanyVatNumber($billingCompanyVatNumber);

        return $this;
    }

    public function getPurchaseOrderNumber(): ?string
    {
        return $this->billing->getPurchaseOrderNumber();
    }

    public function setPurchaseOrderNumber(?string $purchaseOrderNumber): self
    {
        $this->billing->setPurchaseOrderNumber($purchaseOrderNumber);

        return $this;
    }

    public function getBillingEmail(): ?string
    {
        return $this->billing->getBillingEmail();
    }

    public function setBillingEmail(?string $billingEmail): self
    {
        $this->billing->setBillingEmail($billingEmail);

        return $this;
    }

    public function getBillingAddress(): ?string
    {
        return $this->billing->getBillingAddress();
    }

    public function setBillingAddress(?string $billingAddress): self
    {
        $this->billing->setBillingAddress($billingAddress);

        return $this;
    }

    public function getBillingPostalCode(): ?string
    {
        return $this->billing->getBillingPostalCode();
    }

    public function setBillingPostalCode(?string $billingPostalCode): self
    {
        $this->billing->setBillingPostalCode($billingPostalCode);

        return $this;
    }

    public function getBillingCity(): ?string
    {
        return $this->billing->getBillingCity();
    }

    public function setBillingCity(?string $billingCity): self
    {
        $this->billing->setBillingCity($billingCity);

        return $this;
    }
}
