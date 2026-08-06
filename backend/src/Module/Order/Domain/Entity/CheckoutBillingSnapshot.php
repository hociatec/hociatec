<?php

declare(strict_types=1);

namespace App\Module\Order\Domain\Entity;

use App\Module\Order\Domain\ValueObject\CheckoutBillingAddress;

final class CheckoutBillingSnapshot
{
    private string $billingName;
    private ?string $billingCompany;
    private ?string $billingCompanySiren;
    private ?string $billingCompanyVatNumber;
    private ?string $purchaseOrderNumber;
    private ?string $billingEmail;
    private CheckoutBillingAddress $billingAddress;

    /**
     * @param array{
     *     billingName: ?string,
     *     billingCompany: ?string,
     *     billingCompanySiren: ?string,
     *     billingCompanyVatNumber: ?string,
     *     purchaseOrderNumber: ?string,
     *     billingEmail: ?string,
     *     billingAddress: ?string,
     *     billingPostalCode: ?string,
     *     billingCity: ?string,
     * } $payload
     */
    public function __construct(array $payload)
    {
        $this->billingName = $payload['billingName'] ?? '';
        $this->billingCompany = $payload['billingCompany'] ?? null;
        $this->billingCompanySiren = $payload['billingCompanySiren'] ?? null;
        $this->billingCompanyVatNumber = $payload['billingCompanyVatNumber'] ?? null;
        $this->purchaseOrderNumber = $payload['purchaseOrderNumber'] ?? null;
        $this->billingEmail = $payload['billingEmail'] ?? null;
        $this->billingAddress = new CheckoutBillingAddress(
            $payload['billingAddress'] ?? null,
            $payload['billingPostalCode'] ?? null,
            $payload['billingCity'] ?? null,
        );
    }

    public static function fromScalars(
        ?string $billingName,
        ?string $billingCompany,
        ?string $billingCompanySiren,
        ?string $billingCompanyVatNumber,
        ?string $purchaseOrderNumber,
        ?string $billingEmail,
        ?string $billingAddress,
        ?string $billingPostalCode,
        ?string $billingCity,
    ): self {
        return new self([
            'billingName' => $billingName,
            'billingCompany' => $billingCompany,
            'billingCompanySiren' => $billingCompanySiren,
            'billingCompanyVatNumber' => $billingCompanyVatNumber,
            'purchaseOrderNumber' => $purchaseOrderNumber,
            'billingEmail' => $billingEmail,
            'billingAddress' => $billingAddress,
            'billingPostalCode' => $billingPostalCode,
            'billingCity' => $billingCity,
        ]);
    }

    public function name(): string
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
        return $this->billingAddress->address;
    }

    public function postalCode(): ?string
    {
        return $this->billingAddress->postalCode;
    }

    public function city(): ?string
    {
        return $this->billingAddress->city;
    }
}
