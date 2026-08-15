<?php

declare(strict_types=1);

namespace App\Module\User\Domain\Entity;

use App\Module\User\Domain\Entity\Concern\UserAdministrationConcern;
use App\Module\User\Domain\Entity\Concern\UserCommunicationConcern;
use App\Module\User\Domain\Entity\Concern\UserIdentityConcern;
use App\Module\User\Domain\Entity\Concern\UserSecurityConcern;
use App\Module\User\Domain\Security\SecurityUserIdentity;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity]
#[ORM\Table(name: 'users')]
#[ORM\UniqueConstraint(name: 'UNIQ_USERS_EMAIL', columns: ['email'])]
#[ORM\HasLifecycleCallbacks]
class User implements SecurityUserIdentity
{
    use UserAdministrationConcern;
    use UserCommunicationConcern;
    use UserIdentityConcern;
    use UserSecurityConcern;

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
        $this->initializeTimestamps();
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getCreatedAt(): \DateTimeImmutable
    {
        return $this->createdAt;
    }

    public function getUpdatedAt(): \DateTimeImmutable
    {
        return $this->updatedAt;
    }

    public function anonymize(string $email): self
    {
        $this->setEmail($email);
        $this->setFirstName('Deleted');
        $this->setLastName('User');
        $this->setBirthDate(new \DateTimeImmutable('1970-01-01'));
        $this->setPhoneNumber('0000000000');
        $this->setGender('na');
        $this->setRoles(['ROLE_USER']);
        $this->setIsVerified(false);
        $this->setVerificationToken(null);
        $this->setVerificationTokenExpiresAt(null);
        $this->setPasswordResetToken(null);
        $this->setPasswordResetTokenExpiresAt(null);
        $this->setAdminNotes(null);
        $this->setAdminTags([]);
        $this->setCommunicationPreferences([]);
        $this->setAccountNotificationsSeenSignature(null);

        return $this;
    }

    #[ORM\PrePersist]
    public function onPrePersist(): void
    {
        $this->initializeTimestamps();
    }

    #[ORM\PreUpdate]
    public function onPreUpdate(): void
    {
        $this->touch();
    }

    private function initializeTimestamps(): void
    {
        $now = new \DateTimeImmutable();
        $this->createdAt = $now;
        $this->updatedAt = $now;
    }

    private function touch(): void
    {
        $this->updatedAt = new \DateTimeImmutable();
    }
}
