<?php

declare(strict_types=1);

namespace App\Module\User\Domain\Entity;

use App\Module\User\Domain\Security\SecurityUserIdentity;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity]
#[ORM\Table(name: 'users')]
#[ORM\UniqueConstraint(name: 'UNIQ_USERS_EMAIL', columns: ['email'])]
#[ORM\HasLifecycleCallbacks]
class User implements SecurityUserIdentity
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Embedded(class: UserIdentity::class, columnPrefix: false)]
    private UserIdentity $identity;

    #[ORM\Embedded(class: AccountSecurity::class, columnPrefix: false)]
    private AccountSecurity $security;

    #[ORM\Embedded(class: UserAdministrationState::class, columnPrefix: false)]
    private UserAdministrationState $administration;

    #[ORM\Embedded(class: UserCommunicationState::class, columnPrefix: false)]
    private UserCommunicationState $communication;

    #[ORM\Column(type: 'datetime_immutable')]
    private \DateTimeImmutable $createdAt;

    #[ORM\Column(type: 'datetime_immutable')]
    private \DateTimeImmutable $updatedAt;

    public function __construct(
        string $email,
        string $firstName,
        string $lastName,
        \DateTimeImmutable $birthDate,
        string $phoneNumber,
        string $gender,
    ) {
        $this->identity = new UserIdentity($email, $firstName, $lastName, $birthDate, $phoneNumber, $gender);
        $this->security = new AccountSecurity();
        $this->administration = new UserAdministrationState();
        $this->communication = new UserCommunicationState();
        $now = new \DateTimeImmutable();
        $this->createdAt = $now;
        $this->updatedAt = $now;
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getEmail(): string
    {
        return $this->identity->email();
    }

    public function getUserIdentifier(): string
    {
        if ('' === $this->identity->email()) {
            throw new \LogicException('A persisted user must have an email address.');
        }

        return $this->identity->email();
    }

    public function getRoles(): array
    {
        return $this->security->roles();
    }

    public function hasRole(string $role): bool
    {
        return \in_array($role, $this->getRoles(), true);
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

    public function getFirstName(): string
    {
        return $this->identity->firstName();
    }

    public function setFirstName(string $firstName): self
    {
        $this->identity->changeFirstName($firstName);

        return $this;
    }

    public function getLastName(): string
    {
        return $this->identity->lastName();
    }

    public function setLastName(string $lastName): self
    {
        $this->identity->changeLastName($lastName);

        return $this;
    }

    public function setEmail(string $email): self
    {
        $this->identity->changeEmail($email);

        return $this;
    }

    // address/postalCode/city accessors removed

    public function getBirthDate(): \DateTimeImmutable
    {
        return $this->identity->birthDate();
    }

    public function setBirthDate(\DateTimeImmutable $birthDate): self
    {
        $this->identity->changeBirthDate($birthDate);

        return $this;
    }

    public function getPhoneNumber(): string
    {
        return $this->identity->phoneNumber();
    }

    public function setPhoneNumber(string $phoneNumber): self
    {
        $this->identity->changePhoneNumber($phoneNumber);

        return $this;
    }

    public function getGender(): string
    {
        return $this->identity->gender();
    }

    public function setGender(string $gender): self
    {
        $this->identity->changeGender($gender);

        return $this;
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

    public function getAdminNotes(): ?string
    {
        return $this->administration->adminNotes();
    }

    public function setAdminNotes(?string $adminNotes): self
    {
        $this->administration->changeAdminNotes($adminNotes);

        return $this;
    }

    /**
     * @return list<string>
     */
    public function getAdminTags(): array
    {
        return $this->administration->adminTags();
    }

    /**
     * @param list<string> $adminTags
     */
    public function setAdminTags(array $adminTags): self
    {
        $this->administration->changeAdminTags($adminTags);

        return $this;
    }

    public function getLoyaltyPointsBalance(): int
    {
        return $this->administration->loyaltyPointsBalance();
    }

    public function setLoyaltyPointsBalance(int $loyaltyPointsBalance): self
    {
        $this->administration->changeLoyaltyPointsBalance($loyaltyPointsBalance);

        return $this;
    }

    public function addLoyaltyPoints(int $points): self
    {
        $this->administration->addLoyaltyPoints($points);

        return $this;
    }

    public function getAccountNotificationsSeenSignature(): ?string
    {
        return $this->communication->accountNotificationsSeenSignature();
    }

    public function setAccountNotificationsSeenSignature(?string $signature): self
    {
        $this->communication->changeAccountNotificationsSeenSignature($signature);

        return $this;
    }

    /**
     * @return list<string>
     */
    public function getCommunicationPreferences(): array
    {
        return $this->communication->communicationPreferences();
    }

    /**
     * @param list<string> $preferences
     */
    public function setCommunicationPreferences(array $preferences): self
    {
        $this->communication->changeCommunicationPreferences($preferences);

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

    public function getFullName(): string
    {
        return $this->identity->fullName();
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

    public function getCreatedAt(): \DateTimeImmutable
    {
        return $this->createdAt;
    }

    public function getUpdatedAt(): \DateTimeImmutable
    {
        return $this->updatedAt;
    }

    #[ORM\PrePersist]
    public function onPrePersist(): void
    {
        $now = new \DateTimeImmutable();
        $this->createdAt = $now;
        $this->updatedAt = $now;
    }

    #[ORM\PreUpdate]
    public function onPreUpdate(): void
    {
        $this->updatedAt = new \DateTimeImmutable();
    }
}
