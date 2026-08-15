<?php

declare(strict_types=1);

namespace App\Module\Auth\Domain\Entity;

use App\Module\Auth\Domain\ValueObject\RefreshTokenAccessContext;
use App\Module\User\Domain\Entity\User;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity]
#[ORM\Table(name: 'auth_refresh_tokens')]
#[ORM\Index(columns: ['expires_at'], name: 'idx_auth_refresh_tokens_expires_at')]
class RefreshToken
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\ManyToOne(targetEntity: User::class)]
    #[ORM\JoinColumn(nullable: false, onDelete: 'CASCADE')]
    private User $user;

    #[ORM\Column(length: 64, unique: true)]
    private string $selector;

    #[ORM\Column(name: 'token_hash', length: 255)]
    private string $tokenHash;

    #[ORM\Column(name: 'expires_at', type: 'datetime_immutable')]
    private \DateTimeImmutable $expiresAt;

    #[ORM\Column(name: 'created_at', type: 'datetime_immutable')]
    private \DateTimeImmutable $createdAt;

    #[ORM\Column(name: 'last_used_at', type: 'datetime_immutable', nullable: true)]
    private ?\DateTimeImmutable $lastUsedAt = null;

    #[ORM\Column(name: 'revoked_at', type: 'datetime_immutable', nullable: true)]
    private ?\DateTimeImmutable $revokedAt = null;

    #[ORM\Column(name: 'device_label', length: 180, nullable: true)]
    private ?string $deviceLabel = null;

    #[ORM\Column(name: 'device_identifier', length: 128, nullable: true)]
    private ?string $deviceIdentifier = null;

    #[ORM\Column(name: 'platform_label', length: 120, nullable: true)]
    private ?string $platformLabel = null;

    #[ORM\Column(name: 'client_label', length: 120, nullable: true)]
    private ?string $clientLabel = null;

    #[ORM\Column(name: 'location_label', length: 180, nullable: true)]
    private ?string $locationLabel = null;

    #[ORM\Column(name: 'user_agent', length: 512, nullable: true)]
    private ?string $userAgent = null;

    #[ORM\Column(name: 'ip_address', length: 64, nullable: true)]
    private ?string $ipAddress = null;

    public function __construct(
        User $user,
        string $selector,
        string $tokenHash,
        \DateTimeImmutable $expiresAt,
        ?RefreshTokenAccessContext $accessContext = null,
        ?\DateTimeImmutable $createdAt = null,
        ?\DateTimeImmutable $lastUsedAt = null,
    ) {
        $this->user = $user;
        $this->selector = $selector;
        $this->tokenHash = $tokenHash;
        $this->expiresAt = $expiresAt;
        $this->applyAccessContext($accessContext);
        $this->createdAt = $createdAt ?? new \DateTimeImmutable();
        $this->lastUsedAt = $lastUsedAt ?? $this->createdAt;
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getUser(): User
    {
        return $this->user;
    }

    public function getSelector(): string
    {
        return $this->selector;
    }

    public function getTokenHash(): string
    {
        return $this->tokenHash;
    }

    public function getExpiresAt(): \DateTimeImmutable
    {
        return $this->expiresAt;
    }

    public function getCreatedAt(): \DateTimeImmutable
    {
        return $this->createdAt;
    }

    public function getLastUsedAt(): ?\DateTimeImmutable
    {
        return $this->lastUsedAt;
    }

    public function getRevokedAt(): ?\DateTimeImmutable
    {
        return $this->revokedAt;
    }

    public function getDeviceLabel(): ?string
    {
        return $this->deviceLabel;
    }

    public function getDeviceIdentifier(): ?string
    {
        return $this->deviceIdentifier;
    }

    public function getPlatformLabel(): ?string
    {
        return $this->platformLabel;
    }

    public function getClientLabel(): ?string
    {
        return $this->clientLabel;
    }

    public function getLocationLabel(): ?string
    {
        return $this->locationLabel;
    }

    public function getUserAgent(): ?string
    {
        return $this->userAgent;
    }

    public function getIpAddress(): ?string
    {
        return $this->ipAddress;
    }

    public function updateAccessContext(
        ?RefreshTokenAccessContext $accessContext,
    ): self {
        $this->deviceIdentifier = self::normalizeOptionalText($accessContext?->deviceIdentifier) ?? $this->deviceIdentifier;
        $this->deviceLabel = self::normalizeOptionalText($accessContext?->deviceLabel) ?? $this->deviceLabel;
        $this->platformLabel = self::normalizeOptionalText($accessContext?->platformLabel) ?? $this->platformLabel;
        $this->clientLabel = self::normalizeOptionalText($accessContext?->clientLabel) ?? $this->clientLabel;
        $this->locationLabel = self::normalizeOptionalText($accessContext?->locationLabel) ?? $this->locationLabel;
        $this->userAgent = self::normalizeOptionalText($accessContext?->userAgent) ?? $this->userAgent;
        $this->ipAddress = self::normalizeOptionalText($accessContext?->ipAddress) ?? $this->ipAddress;
        $this->lastUsedAt = new \DateTimeImmutable();

        return $this;
    }

    public function revoke(): self
    {
        $this->revokedAt = new \DateTimeImmutable();

        return $this;
    }

    public function isExpired(): bool
    {
        return $this->expiresAt <= new \DateTimeImmutable();
    }

    public function isRevoked(): bool
    {
        return null !== $this->revokedAt;
    }

    private function applyAccessContext(?RefreshTokenAccessContext $accessContext): void
    {
        $this->deviceIdentifier = self::normalizeOptionalText($accessContext?->deviceIdentifier);
        $this->deviceLabel = self::normalizeOptionalText($accessContext?->deviceLabel);
        $this->platformLabel = self::normalizeOptionalText($accessContext?->platformLabel);
        $this->clientLabel = self::normalizeOptionalText($accessContext?->clientLabel);
        $this->locationLabel = self::normalizeOptionalText($accessContext?->locationLabel);
        $this->userAgent = self::normalizeOptionalText($accessContext?->userAgent);
        $this->ipAddress = self::normalizeOptionalText($accessContext?->ipAddress);
    }

    private static function normalizeOptionalText(?string $value): ?string
    {
        if (null === $value) {
            return null;
        }

        $normalized = trim($value);

        return '' === $normalized ? null : $normalized;
    }
}
