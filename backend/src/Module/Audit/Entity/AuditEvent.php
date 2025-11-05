<?php

declare(strict_types=1);

namespace App\Module\Audit\Entity;

use App\Module\Audit\Repository\AuditEventRepository;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: AuditEventRepository::class)]
#[ORM\Table(name: 'audit_events')]
class AuditEvent
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\ManyToOne]
    #[ORM\JoinColumn(nullable: false, onDelete: 'CASCADE')]
    private AuditRequest $audit;

    #[ORM\Column(length: 50)]
    private string $type;

    #[ORM\Column(type: 'text', nullable: true)]
    private ?string $message = null;

    #[ORM\Column(type: 'integer', nullable: true)]
    private ?int $actorUserId = null;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $actorName = null;

    #[ORM\Column(type: 'datetime_immutable')]
    private \DateTimeImmutable $createdAt;

    public function __construct(AuditRequest $audit, string $type, ?string $message, ?int $actorUserId, ?string $actorName)
    {
        $this->audit = $audit;
        $this->type = $type;
        $this->message = $message;
        $this->actorUserId = $actorUserId;
        $this->actorName = $actorName;
        $this->createdAt = new \DateTimeImmutable();
    }

    public function getId(): ?int { return $this->id; }
    public function getAudit(): AuditRequest { return $this->audit; }
    public function getType(): string { return $this->type; }
    public function getMessage(): ?string { return $this->message; }
    public function getActorUserId(): ?int { return $this->actorUserId; }
    public function getActorName(): ?string { return $this->actorName; }
    public function getCreatedAt(): \DateTimeImmutable { return $this->createdAt; }
}

