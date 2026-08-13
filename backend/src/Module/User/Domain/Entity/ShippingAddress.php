<?php

declare(strict_types=1);

namespace App\Module\User\Domain\Entity;

use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity]
#[ORM\Table(name: 'user_shipping_addresses')]
class ShippingAddress
{
    public const TYPE_PERSONAL = 'personal';
    public const TYPE_PROFESSIONAL = 'professional';

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

    #[ORM\Column(length: 180, nullable: true)]
    private ?string $addressComplement = null;

    #[ORM\Column(length: 20)]
    private string $postalCode;

    #[ORM\Column(length: 100)]
    private string $city;

    #[ORM\Column(length: 20, options: ['default' => self::TYPE_PERSONAL])]
    private string $type = self::TYPE_PERSONAL;

    #[ORM\Column(length: 180, nullable: true)]
    private ?string $company = null;

    #[ORM\Column(length: 20, nullable: true)]
    private ?string $companySiren = null;

    #[ORM\Column(length: 32, nullable: true)]
    private ?string $companyVatNumber = null;

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

    public function getAddressComplement(): ?string
    {
        return $this->addressComplement;
    }

    public function getCity(): string
    {
        return $this->city;
    }

    public function getType(): string
    {
        return $this->type;
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

    public function isProfessional(): bool
    {
        return self::TYPE_PROFESSIONAL === $this->type;
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

    public function setAddressComplement(?string $addressComplement): self
    {
        $this->addressComplement = $addressComplement;

        return $this;
    }

    public function setCity(string $city): self
    {
        $this->city = $city;

        return $this;
    }

    public function setType(string $type): self
    {
        $normalized = trim($type);
        $this->type = self::TYPE_PROFESSIONAL === $normalized ? self::TYPE_PROFESSIONAL : self::TYPE_PERSONAL;

        if (self::TYPE_PERSONAL === $this->type) {
            $this->company = null;
            $this->companySiren = null;
            $this->companyVatNumber = null;
        }

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

    public function setIsDefault(bool $isDefault): self
    {
        $this->isDefault = $isDefault;

        return $this;
    }
}
