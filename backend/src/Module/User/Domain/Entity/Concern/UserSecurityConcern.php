<?php

declare(strict_types=1);

namespace App\Module\User\Domain\Entity\Concern;

trait UserSecurityConcern
{
    public function getRoles(): array
    {
        return $this->security->roles();
    }

    public function hasRole(string $role): bool
    {
        return $this->security->hasRole($role);
    }

    public function isAdmin(): bool
    {
        return $this->hasRole('ROLE_ADMIN');
    }

    /** @param list<string> $roles */
    public function setRoles(array $roles): self
    {
        $this->security->changeRoles($roles);

        return $this;
    }

    public function getPassword(): string
    {
        return $this->security->password();
    }

    public function setPassword(string $hashedPassword): self
    {
        $this->security->changePassword($hashedPassword);

        return $this;
    }

    public function eraseCredentials(): void
    {
    }

    public function isVerified(): bool
    {
        return $this->security->isVerified();
    }

    public function setIsVerified(bool $isVerified): self
    {
        $this->security->changeVerified($isVerified);

        return $this;
    }

    public function getVerificationToken(): ?string
    {
        return $this->security->verificationToken();
    }

    public function setVerificationToken(?string $verificationToken): self
    {
        $this->security->changeVerificationToken($verificationToken);

        return $this;
    }

    public function getVerificationTokenExpiresAt(): ?\DateTimeImmutable
    {
        return $this->security->verificationTokenExpiresAt();
    }

    public function setVerificationTokenExpiresAt(?\DateTimeImmutable $expiresAt): self
    {
        $this->security->changeVerificationTokenExpiresAt($expiresAt);

        return $this;
    }

    public function getPasswordResetToken(): ?string
    {
        return $this->security->passwordResetToken();
    }

    public function setPasswordResetToken(?string $passwordResetToken): self
    {
        $this->security->changePasswordResetToken($passwordResetToken);

        return $this;
    }

    public function getPasswordResetTokenExpiresAt(): ?\DateTimeImmutable
    {
        return $this->security->passwordResetTokenExpiresAt();
    }

    public function setPasswordResetTokenExpiresAt(?\DateTimeImmutable $passwordResetTokenExpiresAt): self
    {
        $this->security->changePasswordResetTokenExpiresAt($passwordResetTokenExpiresAt);

        return $this;
    }
}
