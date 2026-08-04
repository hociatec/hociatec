<?php

declare(strict_types=1);

namespace App\Module\User\Domain\Entity;

use Doctrine\ORM\Mapping as ORM;

#[ORM\Embeddable]
final class AccountSecurity
{
    /** @var list<string> */
    #[ORM\Column(type: 'json')]
    private array $roles = [];

    #[ORM\Column]
    private string $password;

    #[ORM\Column(type: 'boolean', options: ['default' => false])]
    private bool $isVerified = false;

    #[ORM\Column(length: 100, nullable: true, unique: true)]
    private ?string $verificationToken = null;

    #[ORM\Column(type: 'datetime_immutable', nullable: true)]
    private ?\DateTimeImmutable $verificationTokenExpiresAt = null;

    #[ORM\Column(length: 100, nullable: true, unique: true)]
    private ?string $passwordResetToken = null;

    #[ORM\Column(type: 'datetime_immutable', nullable: true)]
    private ?\DateTimeImmutable $passwordResetTokenExpiresAt = null;

    public function __construct()
    {
        $this->roles = ['ROLE_USER'];
    }

    /** @return list<string> */
    public function roles(): array
    {
        $roles = $this->roles;
        $roles[] = 'ROLE_USER';

        return array_values(array_unique($roles));
    }

    /** @param list<string> $roles */
    public function changeRoles(array $roles): void
    {
        $this->roles = $roles;
    }

    public function password(): string
    {
        return $this->password;
    }

    public function changePassword(string $hashedPassword): void
    {
        $this->password = $hashedPassword;
    }

    public function isVerified(): bool
    {
        return $this->isVerified;
    }

    public function changeVerified(bool $verified): void
    {
        $this->isVerified = $verified;
    }

    public function verificationToken(): ?string
    {
        return $this->verificationToken;
    }

    public function changeVerificationToken(?string $token): void
    {
        $this->verificationToken = $token;
    }

    public function verificationTokenExpiresAt(): ?\DateTimeImmutable
    {
        return $this->verificationTokenExpiresAt;
    }

    public function changeVerificationTokenExpiresAt(?\DateTimeImmutable $expiresAt): void
    {
        $this->verificationTokenExpiresAt = $expiresAt;
    }

    public function passwordResetToken(): ?string
    {
        return $this->passwordResetToken;
    }

    public function changePasswordResetToken(?string $token): void
    {
        $this->passwordResetToken = $token;
    }

    public function passwordResetTokenExpiresAt(): ?\DateTimeImmutable
    {
        return $this->passwordResetTokenExpiresAt;
    }

    public function changePasswordResetTokenExpiresAt(?\DateTimeImmutable $expiresAt): void
    {
        $this->passwordResetTokenExpiresAt = $expiresAt;
    }
}
