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

    public function __construct(?string ...$values)
    {
        $keys = ['billingName', 'billingCompany', 'billingCompanySiren', 'billingCompanyVatNumber', 'purchaseOrderNumber', 'billingEmail', 'billingAddress', 'billingPostalCode', 'billingCity'];
        $data = array_fill_keys($keys, null);
        foreach ($values as $index => $value) {
            if (!is_int($index)) {
                continue;
            }
            if (isset($keys[$index])) {
                $data[$keys[$index]] = $value;
            }
        }
        $data = array_replace($data, array_filter($values, 'is_string', ARRAY_FILTER_USE_KEY));
        $this->billingName = $data['billingName'];
        $this->billingCompany = $data['billingCompany'];
        $this->billingCompanySiren = $data['billingCompanySiren'];
        $this->billingCompanyVatNumber = $data['billingCompanyVatNumber'];
        $this->purchaseOrderNumber = $data['purchaseOrderNumber'];
        $this->billingEmail = $data['billingEmail'];
        $this->billingAddress = $data['billingAddress'];
        $this->billingPostalCode = $data['billingPostalCode'];
        $this->billingCity = $data['billingCity'];
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
