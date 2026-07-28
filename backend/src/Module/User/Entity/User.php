<?php

declare(strict_types=1);

namespace App\Module\User\Entity;

use App\Module\User\Repository\UserRepository;
use App\Shared\Normalization\EmailNormalizer;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Security\Core\User\PasswordAuthenticatedUserInterface;
use Symfony\Component\Security\Core\User\UserInterface;

#[ORM\Entity(repositoryClass: UserRepository::class)]
#[ORM\Table(name: 'users')]
#[ORM\UniqueConstraint(name: 'UNIQ_USERS_EMAIL', columns: ['email'])]
#[ORM\HasLifecycleCallbacks]
class User implements UserInterface, PasswordAuthenticatedUserInterface
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(length: 180)]
    private string $email;

    /** @var list<string> */
    #[ORM\Column(type: 'json')]
    private array $roles = [];

    #[ORM\Column]
    private string $password;

    #[ORM\Column(length: 50)]
    private string $firstName;

    #[ORM\Column(length: 50)]
    private string $lastName;

    // Address fields removed; managed via ShippingAddress entity

    #[ORM\Column(type: 'date_immutable')]
    private \DateTimeImmutable $birthDate;

    #[ORM\Column(length: 20)]
    private string $phoneNumber;

    #[ORM\Column(length: 10)]
    private string $gender;

    #[ORM\Column(type: 'boolean', options: ['default' => false])]
    private bool $isVerified = false;

    #[ORM\Column(type: 'text', nullable: true)]
    private ?string $adminNotes = null;

    /** @var list<string> */
    #[ORM\Column(type: 'json')]
    private array $adminTags = [];

    #[ORM\Column(type: 'integer', options: ['default' => 0])]
    private int $loyaltyPointsBalance = 0;

    #[ORM\Column(type: 'text', nullable: true)]
    private ?string $accountNotificationsSeenSignature = null;

    /** @var list<string> */
    #[ORM\Column(type: 'json', nullable: true)]
    private ?array $communicationPreferences = null;

    #[ORM\Column(length: 100, nullable: true, unique: true)]
    private ?string $verificationToken = null;

    #[ORM\Column(type: 'datetime_immutable', nullable: true)]
    private ?\DateTimeImmutable $verificationTokenExpiresAt = null;

    #[ORM\Column(length: 100, nullable: true, unique: true)]
    private ?string $passwordResetToken = null;

    #[ORM\Column(type: 'datetime_immutable', nullable: true)]
    private ?\DateTimeImmutable $passwordResetTokenExpiresAt = null;

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
        $this->email = EmailNormalizer::normalize($email);
        $this->firstName = $firstName;
        $this->lastName = $lastName;
        $this->birthDate = $birthDate;
        $this->phoneNumber = $phoneNumber;
        $this->gender = $gender;
        $this->roles = ['ROLE_USER'];
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
        return $this->email;
    }

    public function getUserIdentifier(): string
    {
        if ('' === $this->email) {
            throw new \LogicException('A persisted user must have an email address.');
        }

        return $this->email;
    }

    public function getRoles(): array
    {
        $roles = $this->roles;
        $roles[] = 'ROLE_USER';

        return array_values(array_unique($roles));
    }

    /** @param list<string> $roles */
    public function setRoles(array $roles): self
    {
        $this->roles = $roles;

        return $this;
    }

    public function getPassword(): string
    {
        return $this->password;
    }

    public function setPassword(string $hashedPassword): self
    {
        $this->password = $hashedPassword;

        return $this;
    }

    public function eraseCredentials(): void
    {
    }

    public function getFirstName(): string
    {
        return $this->firstName;
    }

    public function setFirstName(string $firstName): self
    {
        $this->firstName = $firstName;

        return $this;
    }

    public function getLastName(): string
    {
        return $this->lastName;
    }

    public function setLastName(string $lastName): self
    {
        $this->lastName = $lastName;

        return $this;
    }

    public function setEmail(string $email): self
    {
        $this->email = EmailNormalizer::normalize($email);

        return $this;
    }

    // address/postalCode/city accessors removed

    public function getBirthDate(): \DateTimeImmutable
    {
        return $this->birthDate;
    }

    public function setBirthDate(\DateTimeImmutable $birthDate): self
    {
        $this->birthDate = $birthDate;

        return $this;
    }

    public function getPhoneNumber(): string
    {
        return $this->phoneNumber;
    }

    public function setPhoneNumber(string $phoneNumber): self
    {
        $this->phoneNumber = $phoneNumber;

        return $this;
    }

    public function getGender(): string
    {
        return $this->gender;
    }

    public function setGender(string $gender): self
    {
        $this->gender = $gender;

        return $this;
    }

    public function isVerified(): bool
    {
        return $this->isVerified;
    }

    public function setIsVerified(bool $isVerified): self
    {
        $this->isVerified = $isVerified;

        return $this;
    }

    public function getAdminNotes(): ?string
    {
        return $this->adminNotes;
    }

    public function setAdminNotes(?string $adminNotes): self
    {
        $this->adminNotes = null !== $adminNotes ? trim($adminNotes) : null;

        return $this;
    }

    /**
     * @return list<string>
     */
    public function getAdminTags(): array
    {
        return array_values(array_filter(
            array_map(static fn (mixed $tag): string => trim((string) $tag), $this->adminTags),
            static fn (string $tag): bool => '' !== $tag,
        ));
    }

    /**
     * @param list<string> $adminTags
     */
    public function setAdminTags(array $adminTags): self
    {
        $normalized = [];
        foreach ($adminTags as $tag) {
            $value = trim((string) $tag);
            if ('' === $value) {
                continue;
            }

            $normalized[] = $value;
        }

        $this->adminTags = array_values(array_unique($normalized));

        return $this;
    }

    public function getLoyaltyPointsBalance(): int
    {
        return $this->loyaltyPointsBalance;
    }

    public function setLoyaltyPointsBalance(int $loyaltyPointsBalance): self
    {
        $this->loyaltyPointsBalance = max(0, $loyaltyPointsBalance);

        return $this;
    }

    public function addLoyaltyPoints(int $points): self
    {
        $this->loyaltyPointsBalance = max(0, $this->loyaltyPointsBalance + $points);

        return $this;
    }

    public function getAccountNotificationsSeenSignature(): ?string
    {
        return $this->accountNotificationsSeenSignature;
    }

    public function setAccountNotificationsSeenSignature(?string $signature): self
    {
        $signature = null !== $signature ? trim($signature) : null;
        $this->accountNotificationsSeenSignature = '' !== $signature ? $signature : null;

        return $this;
    }

    /**
     * @return list<string>
     */
    public function getCommunicationPreferences(): array
    {
        $preferences = is_array($this->communicationPreferences)
            ? $this->communicationPreferences
            : ['notification', 'email'];

        return array_values(array_unique(array_filter(
            $preferences,
            static fn (string $preference): bool => in_array($preference, ['notification', 'email', 'news_email', 'phone'], true),
        )));
    }

    /**
     * @param list<string> $preferences
     */
    public function setCommunicationPreferences(array $preferences): self
    {
        $this->communicationPreferences = array_values(array_unique(array_filter(
            $preferences,
            static fn (string $preference): bool => in_array($preference, ['notification', 'email', 'news_email', 'phone'], true),
        )));

        return $this;
    }

    public function getVerificationToken(): ?string
    {
        return $this->verificationToken;
    }

    public function setVerificationToken(?string $verificationToken): self
    {
        $this->verificationToken = $verificationToken;

        return $this;
    }

    public function getVerificationTokenExpiresAt(): ?\DateTimeImmutable
    {
        return $this->verificationTokenExpiresAt;
    }

    public function setVerificationTokenExpiresAt(?\DateTimeImmutable $expiresAt): self
    {
        $this->verificationTokenExpiresAt = $expiresAt;

        return $this;
    }

    public function getFullName(): string
    {
        return sprintf('%s %s', $this->firstName, $this->lastName);
    }

    public function getPasswordResetToken(): ?string
    {
        return $this->passwordResetToken;
    }

    public function setPasswordResetToken(?string $passwordResetToken): self
    {
        $this->passwordResetToken = $passwordResetToken;

        return $this;
    }

    public function getPasswordResetTokenExpiresAt(): ?\DateTimeImmutable
    {
        return $this->passwordResetTokenExpiresAt;
    }

    public function setPasswordResetTokenExpiresAt(?\DateTimeImmutable $passwordResetTokenExpiresAt): self
    {
        $this->passwordResetTokenExpiresAt = $passwordResetTokenExpiresAt;

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
