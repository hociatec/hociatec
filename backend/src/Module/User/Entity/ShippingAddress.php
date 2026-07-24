<?php

declare(strict_types=1);

namespace App\Module\User\Entity;

use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity]
#[ORM\Table(name: 'user_shipping_addresses')]
class ShippingAddress
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\ManyToOne]
    #[ORM\JoinColumn(nullable: false, onDelete: 'CASCADE')]
    private User $user;

    #[ORM\Column(length: 180)]
    private string $name;

    #[ORM\Column(type: 'text')]
    private string $address;

    #[ORM\Column(length: 20)]
    private string $postalCode;

    #[ORM\Column(length: 100)]
    private string $city;

    #[ORM\Column(length: 180, nullable: true)]
    private ?string $company = null;

    #[ORM\Column(length: 20, nullable: true)]
    private ?string $companySiren = null;

    #[ORM\Column(length: 32, nullable: true)]
    private ?string $companyVatNumber = null;

    #[ORM\Column(length: 80, nullable: true)]
    private ?string $purchaseOrderNumber = null;

    #[ORM\Column(type: 'boolean', options: ['default' => false])]
    private bool $isDefault = false;

    public function __construct(User $user, string $name, string $address, string $postalCode, string $city)
    {
        $this->user = $user;
        $this->name = $name;
        $this->address = $address;
        $this->postalCode = $postalCode;
        $this->city = $city;
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getUser(): User
    {
        return $this->user;
    }

    public function getName(): string
    {
        return $this->name;
    }

    public function getAddress(): string
    {
        return $this->address;
    }

    public function getPostalCode(): string
    {
        return $this->postalCode;
    }

    public function getCity(): string
    {
        return $this->city;
    }

    public function getCompany(): ?string
    {
        return $this->company;
    }

    public function getCompanySiren(): ?string
    {
        return $this->companySiren;
    }

    public function getCompanyVatNumber(): ?string
    {
        return $this->companyVatNumber;
    }

    public function getPurchaseOrderNumber(): ?string
    {
        return $this->purchaseOrderNumber;
    }

    public function isDefault(): bool
    {
        return $this->isDefault;
    }

    public function setName(string $name): self
    {
        $this->name = $name;

        return $this;
    }

    public function setAddress(string $address): self
    {
        $this->address = $address;

        return $this;
    }

    public function setPostalCode(string $postalCode): self
    {
        $this->postalCode = $postalCode;

        return $this;
    }

    public function setCity(string $city): self
    {
        $this->city = $city;

        return $this;
    }

    public function setCompany(?string $company): self
    {
        $this->company = $company;

        return $this;
    }

    public function setCompanySiren(?string $companySiren): self
    {
        $this->companySiren = $companySiren;

        return $this;
    }

    public function setCompanyVatNumber(?string $companyVatNumber): self
    {
        $this->companyVatNumber = $companyVatNumber;

        return $this;
    }

    public function setPurchaseOrderNumber(?string $purchaseOrderNumber): self
    {
        $this->purchaseOrderNumber = $purchaseOrderNumber;

        return $this;
    }

    public function setIsDefault(bool $isDefault): self
    {
        $this->isDefault = $isDefault;

        return $this;
    }
}
