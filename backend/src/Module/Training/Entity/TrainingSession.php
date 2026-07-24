<?php

declare(strict_types=1);

namespace App\Module\Training\Entity;

use App\Module\Training\Repository\TrainingSessionRepository;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: TrainingSessionRepository::class)]
#[ORM\Table(name: 'training_sessions')]
#[ORM\Index(name: 'IDX_TRAINING_SESSION_TRAINING', columns: ['training_id'])]
#[ORM\Index(name: 'IDX_TRAINING_SESSION_STARTS', fields: ['startsAt'])]
#[ORM\HasLifecycleCallbacks]
class TrainingSession
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\ManyToOne(targetEntity: Training::class)]
    #[ORM\JoinColumn(nullable: false, onDelete: 'CASCADE')]
    private Training $training;

    #[ORM\Column(length: 20)]
    private string $format;

    #[ORM\Column(type: 'datetime_immutable')]
    private \DateTimeImmutable $startsAt;

    #[ORM\Column(type: 'datetime_immutable')]
    private \DateTimeImmutable $endsAt;

    #[ORM\Column(type: 'time_immutable', options: ['default' => '08:00:00'])]
    private \DateTimeImmutable $dailyStartTime;

    #[ORM\Column(type: 'time_immutable', options: ['default' => '20:00:00'])]
    private \DateTimeImmutable $dailyEndTime;

    #[ORM\Column(type: 'boolean', options: ['default' => true])]
    private bool $includeWeekends = true;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $location = null;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $meetingUrl = null;

    #[ORM\Column(type: 'integer')]
    private int $capacity;

    #[ORM\Column(length: 30)]
    private string $status = 'scheduled';

    #[ORM\Column(type: 'datetime_immutable')]
    private \DateTimeImmutable $createdAt;

    #[ORM\Column(type: 'datetime_immutable')]
    private \DateTimeImmutable $updatedAt;

    public function __construct(Training $training, string $format, \DateTimeImmutable $startsAt, \DateTimeImmutable $endsAt, int $capacity)
    {
        $this->training = $training;
        $this->format = $format;
        $this->startsAt = $startsAt;
        $this->endsAt = $endsAt;
        $this->dailyStartTime = new \DateTimeImmutable('08:00');
        $this->dailyEndTime = new \DateTimeImmutable('20:00');
        $this->capacity = $capacity;
        $now = new \DateTimeImmutable();
        $this->createdAt = $now;
        $this->updatedAt = $now;
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getTraining(): Training
    {
        return $this->training;
    }

    public function setTraining(Training $training): self
    {
        $this->training = $training;

        return $this;
    }

    public function getFormat(): string
    {
        return $this->format;
    }

    public function setFormat(string $format): self
    {
        $this->format = $format;

        return $this;
    }

    public function getStartsAt(): \DateTimeImmutable
    {
        return $this->startsAt;
    }

    public function setStartsAt(\DateTimeImmutable $startsAt): self
    {
        $this->startsAt = $startsAt;

        return $this;
    }

    public function getEndsAt(): \DateTimeImmutable
    {
        return $this->endsAt;
    }

    public function setEndsAt(\DateTimeImmutable $endsAt): self
    {
        $this->endsAt = $endsAt;

        return $this;
    }

    public function getDailyStartTime(): \DateTimeImmutable
    {
        return $this->dailyStartTime;
    }

    public function setDailyStartTime(\DateTimeImmutable $dailyStartTime): self
    {
        $this->dailyStartTime = $dailyStartTime;

        return $this;
    }

    public function getDailyEndTime(): \DateTimeImmutable
    {
        return $this->dailyEndTime;
    }

    public function setDailyEndTime(\DateTimeImmutable $dailyEndTime): self
    {
        $this->dailyEndTime = $dailyEndTime;

        return $this;
    }

    public function includesWeekends(): bool
    {
        return $this->includeWeekends;
    }

    public function setIncludeWeekends(bool $includeWeekends): self
    {
        $this->includeWeekends = $includeWeekends;

        return $this;
    }

    public function getLocation(): ?string
    {
        return $this->location;
    }

    public function setLocation(?string $location): self
    {
        $this->location = $location;

        return $this;
    }

    public function getMeetingUrl(): ?string
    {
        return $this->meetingUrl;
    }

    public function setMeetingUrl(?string $meetingUrl): self
    {
        $this->meetingUrl = $meetingUrl;

        return $this;
    }

    public function getCapacity(): int
    {
        return $this->capacity;
    }

    public function setCapacity(int $capacity): self
    {
        $this->capacity = $capacity;

        return $this;
    }

    public function getStatus(): string
    {
        return $this->status;
    }

    public function setStatus(string $status): self
    {
        $this->status = $status;

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

    #[ORM\PreUpdate]
    public function touch(): void
    {
        $this->updatedAt = new \DateTimeImmutable();
    }
}
