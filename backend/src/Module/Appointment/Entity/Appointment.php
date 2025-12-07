<?php

declare(strict_types=1);

namespace App\Module\Appointment\Entity;

use App\Module\Appointment\Repository\AppointmentRepository;
use App\Module\User\Entity\User;
use DateTimeImmutable;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: AppointmentRepository::class)]
#[ORM\Table(name: 'appointments')]
#[ORM\Index(columns: ['start_at'], name: 'idx_appointments_start_at')]
#[ORM\Index(columns: ['end_at'], name: 'idx_appointments_end_at')]
class Appointment
{
    public const STATUS_CONFIRMED = 'confirmed';
    public const STATUS_CANCELLED = 'cancelled';

    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\ManyToOne(targetEntity: User::class)]
    #[ORM\JoinColumn(nullable: false, onDelete: 'CASCADE')]
    private User $user;

    #[ORM\ManyToOne(targetEntity: Prestation::class)]
    #[ORM\JoinColumn(nullable: false, onDelete: 'CASCADE')]
    private Prestation $prestation;

    #[ORM\Column(type: 'datetime_immutable')]
    private DateTimeImmutable $startAt;

    #[ORM\Column(type: 'datetime_immutable')]
    private DateTimeImmutable $endAt;

    #[ORM\Column(length: 20)]
    private string $status;

    #[ORM\Column(type: 'datetime_immutable')]
    private DateTimeImmutable $createdAt;

    public function __construct(User $user, Prestation $prestation, DateTimeImmutable $startAt)
    {
        $this->user = $user;
        $this->prestation = $prestation;
        $this->startAt = $startAt;
        $this->endAt = $startAt->add($prestation->getDurationInterval());
        $this->status = self::STATUS_CONFIRMED;
        $this->createdAt = new DateTimeImmutable();
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getUser(): User
    {
        return $this->user;
    }

    public function getPrestation(): Prestation
    {
        return $this->prestation;
    }

    public function getStartAt(): DateTimeImmutable
    {
        return $this->startAt;
    }

    public function getEndAt(): DateTimeImmutable
    {
        return $this->endAt;
    }

    public function getStatus(): string
    {
        return $this->status;
    }

    public function getCreatedAt(): DateTimeImmutable
    {
        return $this->createdAt;
    }

    public function setStatus(string $status): self
    {
        $this->status = $status;

        return $this;
    }

    public function overlaps(DateTimeImmutable $startAt, DateTimeImmutable $endAt): bool
    {
        return $this->startAt < $endAt && $this->endAt > $startAt;
    }

    public function setStartAt(DateTimeImmutable $startAt): self
    {
        $this->startAt = $startAt;
        $this->endAt = $startAt->add($this->prestation->getDurationInterval());

        return $this;
    }

    public function setEndAt(DateTimeImmutable $endAt): self
    {
        $this->endAt = $endAt;

        return $this;
    }
}
