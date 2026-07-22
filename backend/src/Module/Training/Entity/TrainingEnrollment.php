<?php

declare(strict_types=1);

namespace App\Module\Training\Entity;

use App\Module\Training\Repository\TrainingEnrollmentRepository;
use App\Module\User\Entity\User;
use DateTimeImmutable;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: TrainingEnrollmentRepository::class)]
#[ORM\Table(name: 'training_enrollments')]
#[ORM\UniqueConstraint(name: 'uniq_training_session_user', columns: ['session_id', 'user_id'])]
class TrainingEnrollment
{
    public const STATUS_PENDING_PAYMENT = 'pending_payment';
    public const STATUS_PAID = 'paid';
    public const STATUS_CONFIRMED = 'confirmed';
    public const STATUS_COMPLETED = 'completed';
    public const STATUS_CANCELLED = 'cancelled';

    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\ManyToOne(targetEntity: TrainingSession::class)]
    #[ORM\JoinColumn(name: 'session_id', nullable: false, onDelete: 'CASCADE')]
    private TrainingSession $session;

    #[ORM\ManyToOne(targetEntity: User::class)]
    #[ORM\JoinColumn(nullable: false, onDelete: 'CASCADE')]
    private User $user;

    #[ORM\Column(length: 30)]
    private string $status = self::STATUS_PENDING_PAYMENT;

    #[ORM\Column(type: 'integer')]
    private int $priceCents;

    #[ORM\Column(type: 'datetime_immutable')]
    private DateTimeImmutable $scheduledStartsAt;

    #[ORM\Column(type: 'datetime_immutable')]
    private DateTimeImmutable $scheduledEndsAt;

    #[ORM\Column(type: 'datetime_immutable', nullable: true)]
    private ?DateTimeImmutable $paidAt = null;

    #[ORM\Column(length: 190, nullable: true)]
    private ?string $stripeSessionId = null;

    #[ORM\Column(length: 190, nullable: true)]
    private ?string $stripePaymentIntentId = null;

    #[ORM\Column(type: 'datetime_immutable')]
    private DateTimeImmutable $createdAt;

    public function __construct(TrainingSession $session, User $user, int $priceCents)
    {
        $this->session = $session;
        $this->user = $user;
        $this->priceCents = $priceCents;
        $this->scheduledStartsAt = $session->getStartsAt();
        $this->scheduledEndsAt = $session->getEndsAt();
        $this->createdAt = new DateTimeImmutable();
    }

    public function getId(): ?int { return $this->id; }
    public function getSession(): TrainingSession { return $this->session; }
    public function getUser(): User { return $this->user; }
    public function getStatus(): string { return $this->status; }
    public function setStatus(string $status): self { $this->status = $status; return $this; }
    public function getPriceCents(): int { return $this->priceCents; }
    public function setPriceCents(int $priceCents): self { $this->priceCents = $priceCents; return $this; }
    public function getScheduledStartsAt(): DateTimeImmutable { return $this->scheduledStartsAt; }
    public function setScheduledStartsAt(DateTimeImmutable $scheduledStartsAt): self { $this->scheduledStartsAt = $scheduledStartsAt; return $this; }
    public function getScheduledEndsAt(): DateTimeImmutable { return $this->scheduledEndsAt; }
    public function setScheduledEndsAt(DateTimeImmutable $scheduledEndsAt): self { $this->scheduledEndsAt = $scheduledEndsAt; return $this; }
    public function getPaidAt(): ?DateTimeImmutable { return $this->paidAt; }
    public function setPaidAt(?DateTimeImmutable $paidAt): self { $this->paidAt = $paidAt; return $this; }
    public function getStripeSessionId(): ?string { return $this->stripeSessionId; }
    public function setStripeSessionId(?string $stripeSessionId): self { $this->stripeSessionId = $stripeSessionId; return $this; }
    public function getStripePaymentIntentId(): ?string { return $this->stripePaymentIntentId; }
    public function setStripePaymentIntentId(?string $stripePaymentIntentId): self { $this->stripePaymentIntentId = $stripePaymentIntentId; return $this; }
    public function getCreatedAt(): DateTimeImmutable { return $this->createdAt; }
}
