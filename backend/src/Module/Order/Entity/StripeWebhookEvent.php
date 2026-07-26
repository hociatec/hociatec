<?php

declare(strict_types=1);

namespace App\Module\Order\Entity;

use App\Module\Order\Repository\StripeWebhookEventRepository;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: StripeWebhookEventRepository::class)]
#[ORM\Table(name: 'stripe_webhook_events')]
class StripeWebhookEvent
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(length: 255, unique: true)]
    private string $stripeEventId;

    #[ORM\Column(length: 100)]
    private string $type;

    #[ORM\Column(length: 20)]
    private string $status = 'processing';

    #[ORM\Column(type: 'text', nullable: true)]
    private ?string $errorMessage = null;

    #[ORM\Column(type: 'datetime_immutable')]
    private \DateTimeImmutable $receivedAt;

    #[ORM\Column(type: 'datetime_immutable', nullable: true)]
    private ?\DateTimeImmutable $processedAt = null;

    public function __construct(string $stripeEventId, string $type)
    {
        $this->stripeEventId = $stripeEventId;
        $this->type = $type;
        $this->receivedAt = new \DateTimeImmutable();
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getStripeEventId(): string
    {
        return $this->stripeEventId;
    }

    public function getStatus(): string
    {
        return $this->status;
    }

    public function isProcessed(): bool
    {
        return 'processed' === $this->status;
    }

    public function markProcessed(): void
    {
        $this->status = 'processed';
        $this->processedAt = new \DateTimeImmutable();
        $this->errorMessage = null;
    }

    public function markFailed(string $message): void
    {
        $this->status = 'failed';
        $this->errorMessage = mb_substr($message, 0, 2000);
    }
}
