<?php

declare(strict_types=1);

namespace App\Module\BetaTest\Entity;

use App\Module\BetaTest\Repository\BugReportActivityRepository;
use App\Module\User\Entity\User;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: BugReportActivityRepository::class)]
#[ORM\Table(name: 'beta_bug_report_activities')]
#[ORM\Index(name: 'idx_beta_bug_activity_report', columns: ['bug_report_id'])]
class BugReportActivity
{
    #[ORM\Id, ORM\GeneratedValue, ORM\Column]
    private ?int $id = null;

    #[ORM\ManyToOne(targetEntity: BugReport::class)]
    #[ORM\JoinColumn(name: 'bug_report_id', referencedColumnName: 'id', nullable: false, onDelete: 'CASCADE')]
    private BugReport $bugReport;

    #[ORM\ManyToOne(targetEntity: User::class)]
    #[ORM\JoinColumn(name: 'actor_id', referencedColumnName: 'id', nullable: true, onDelete: 'SET NULL')]
    private ?User $actor;

    #[ORM\Column(length: 60)]
    private string $action;

    #[ORM\Column(length: 190, nullable: true)]
    private ?string $fromValue;

    #[ORM\Column(length: 190, nullable: true)]
    private ?string $toValue;

    #[ORM\Column(type: 'text', nullable: true)]
    private ?string $message;

    #[ORM\Column(type: 'datetime_immutable')]
    private \DateTimeImmutable $createdAt;

    public function __construct(BugReport $bugReport, ?User $actor, string $action, ?string $fromValue = null, ?string $toValue = null, ?string $message = null)
    {
        $this->bugReport = $bugReport;
        $this->actor = $actor;
        $this->action = $action;
        $this->fromValue = $fromValue;
        $this->toValue = $toValue;
        $this->message = null !== $message && '' !== trim($message) ? trim($message) : null;
        $this->createdAt = new \DateTimeImmutable();
    }

    public function getId(): ?int { return $this->id; }
    public function getBugReport(): BugReport { return $this->bugReport; }
    public function getActor(): ?User { return $this->actor; }
    public function getAction(): string { return $this->action; }
    public function getFromValue(): ?string { return $this->fromValue; }
    public function getToValue(): ?string { return $this->toValue; }
    public function getMessage(): ?string { return $this->message; }
    public function getCreatedAt(): \DateTimeImmutable { return $this->createdAt; }
}
