<?php

declare(strict_types=1);

namespace App\Module\Outbox\Domain\Entity;

use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity]
#[ORM\Table(name: 'outbox_events')]
#[ORM\Index(name: 'idx_outbox_pending', columns: ['status', 'available_at', 'created_at'])]
#[ORM\UniqueConstraint(name: 'uniq_outbox_event_key', columns: ['event_key'])]
class OutboxEvent
{
    public const STATUS_PENDING = 'pending';
    public const STATUS_PROCESSING = 'processing';
    public const STATUS_PROCESSED = 'processed';
    public const STATUS_FAILED = 'failed';

    #[ORM\Id, ORM\GeneratedValue, ORM\Column]
    private ?int $id = null;

    #[ORM\Column(name: 'event_key', length: 190)]
    private string $key;

    #[ORM\Column(length: 120)]
    private string $type;

    /** @var array<string, mixed> */
    #[ORM\Column(type: 'json')]
    private array $payload;

    #[ORM\Column(length: 20)]
    private string $status = self::STATUS_PENDING;

    #[ORM\Column]
    private int $attempts = 0;

    #[ORM\Column(name: 'available_at', type: 'datetime_immutable')]
    private \DateTimeImmutable $availableAt;

    #[ORM\Column(name: 'created_at', type: 'datetime_immutable')]
    private \DateTimeImmutable $createdAt;

    #[ORM\Column(name: 'processed_at', type: 'datetime_immutable', nullable: true)]
    private ?\DateTimeImmutable $processedAt = null;

    #[ORM\Column(name: 'last_error', type: 'text', nullable: true)]
    private ?string $lastError = null;

    /** @param array<string, mixed> $payload */
    public function __construct(string $key, string $type, array $payload, ?\DateTimeImmutable $availableAt = null)
    {
        $this->key = $key;
        $this->type = $type;
        $this->payload = $payload;
        $this->createdAt = new \DateTimeImmutable();
        $this->availableAt = $availableAt ?? $this->createdAt;
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getKey(): string
    {
        return $this->key;
    }

    public function getType(): string
    {
        return $this->type;
    }

    /** @return array<string, mixed> */
    public function getPayload(): array
    {
        return $this->payload;
    }

    public function getStatus(): string
    {
        return $this->status;
    }

    public function getAttempts(): int
    {
        return $this->attempts;
    }

    public function getAvailableAt(): \DateTimeImmutable
    {
        return $this->availableAt;
    }

    public function getLastError(): ?string
    {
        return $this->lastError;
    }

    public function markProcessing(): self
    {
        $this->status = self::STATUS_PROCESSING;
        ++$this->attempts;

        return $this;
    }

    public function markProcessed(): self
    {
        $this->status = self::STATUS_PROCESSED;
        $this->processedAt = new \DateTimeImmutable();
        $this->lastError = null;

        return $this;
    }

    public function markFailed(string $message, \DateTimeImmutable $availableAt): self
    {
        $this->status = self::STATUS_FAILED;
        $this->lastError = mb_substr($message, 0, 2000);
        $this->availableAt = $availableAt;

        return $this;
    }

    public function retry(): self
    {
        $this->status = self::STATUS_PENDING;

        return $this;
    }
}
