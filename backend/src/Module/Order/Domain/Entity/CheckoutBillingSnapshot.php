<?php

declare(strict_types=1);

namespace App\Module\Order\Domain\Entity;

use Doctrine\ORM\Mapping as ORM;

#[ORM\Embeddable]
final class CheckoutBillingSnapshot
{
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

    public function name(): ?string { return $this->billingName; }
    public function changeName(?string $billingName): void { $this->billingName = $billingName; }
    public function company(): ?string { return $this->billingCompany; }
    public function changeCompany(?string $billingCompany): void { $this->billingCompany = $billingCompany; }
    public function companySiren(): ?string { return $this->billingCompanySiren; }
    public function changeCompanySiren(?string $billingCompanySiren): void { $this->billingCompanySiren = $billingCompanySiren; }
    public function companyVatNumber(): ?string { return $this->billingCompanyVatNumber; }
    public function changeCompanyVatNumber(?string $billingCompanyVatNumber): void { $this->billingCompanyVatNumber = $billingCompanyVatNumber; }
    public function purchaseOrderNumber(): ?string { return $this->purchaseOrderNumber; }
    public function changePurchaseOrderNumber(?string $purchaseOrderNumber): void { $this->purchaseOrderNumber = $purchaseOrderNumber; }
    public function email(): ?string { return $this->billingEmail; }
    public function changeEmail(?string $billingEmail): void { $this->billingEmail = $billingEmail; }
    public function address(): ?string { return $this->billingAddress; }
    public function changeAddress(?string $billingAddress): void { $this->billingAddress = $billingAddress; }
    public function postalCode(): ?string { return $this->billingPostalCode; }
    public function changePostalCode(?string $billingPostalCode): void { $this->billingPostalCode = $billingPostalCode; }
    public function city(): ?string { return $this->billingCity; }
    public function changeCity(?string $billingCity): void { $this->billingCity = $billingCity; }
}
