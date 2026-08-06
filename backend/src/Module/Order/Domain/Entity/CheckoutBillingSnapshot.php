<?php

declare(strict_types=1);

namespace App\Module\Order\Domain\Entity;

final class CheckoutBillingSnapshot
{
    private ?string $billingName;
    private ?string $billingCompany;
    private ?string $billingCompanySiren;
    private ?string $billingCompanyVatNumber;
    private ?string $purchaseOrderNumber;
    private ?string $billingEmail;
    private ?string $billingAddress;
    private ?string $billingPostalCode;
    private ?string $billingCity;

    public function __construct(
        ?string $billingName,
        ?string $billingCompany,
        ?string $billingCompanySiren,
        ?string $billingCompanyVatNumber,
        ?string $purchaseOrderNumber,
        ?string $billingEmail,
        ?string $billingAddress,
        ?string $billingPostalCode,
        ?string $billingCity,
    ) {
        $this->billingName = $billingName;
        $this->billingCompany = $billingCompany;
        $this->billingCompanySiren = $billingCompanySiren;
        $this->billingCompanyVatNumber = $billingCompanyVatNumber;
        $this->purchaseOrderNumber = $purchaseOrderNumber;
        $this->billingEmail = $billingEmail;
        $this->billingAddress = $billingAddress;
        $this->billingPostalCode = $billingPostalCode;
        $this->billingCity = $billingCity;
    }

    public function name(): ?string
    {
        return $this->billingName;
    }

    public function company(): ?string
    {
        return $this->billingCompany;
    }

    public function companySiren(): ?string
    {
        return $this->billingCompanySiren;
    }

    public function companyVatNumber(): ?string
    {
        return $this->billingCompanyVatNumber;
    }

    public function purchaseOrderNumber(): ?string
    {
        return $this->purchaseOrderNumber;
    }

    public function email(): ?string
    {
        return $this->billingEmail;
    }

    public function address(): ?string
    {
        return $this->billingAddress;
    }

    public function postalCode(): ?string
    {
        return $this->billingPostalCode;
    }

    public function city(): ?string
    {
        return $this->billingCity;
    }
}
