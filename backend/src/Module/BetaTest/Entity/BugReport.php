<?php
declare(strict_types=1);
namespace App\Module\BetaTest\Entity;
use App\Module\BetaTest\Repository\BugReportRepository;
use App\Module\User\Entity\User;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: BugReportRepository::class)]
#[ORM\Table(name: 'beta_bug_reports')]
#[ORM\Index(name: 'idx_beta_bug_status', columns: ['status'])]
final class BugReport
{
    #[ORM\Id, ORM\GeneratedValue, ORM\Column] private ?int $id = null;
    #[ORM\ManyToOne(targetEntity: User::class)] #[ORM\JoinColumn(nullable: false, onDelete: 'CASCADE')] private User $reporter;
    #[ORM\ManyToOne(targetEntity: BetaCampaign::class)] #[ORM\JoinColumn(nullable: true, onDelete: 'SET NULL')] private ?BetaCampaign $campaign;
    #[ORM\Column(length: 180)] private string $title;
    #[ORM\Column(type: 'text')] private string $description;
    #[ORM\Column(type: 'text', nullable: true)] private ?string $expectedBehavior;
    #[ORM\Column(type: 'text', nullable: true)] private ?string $actualBehavior;
    #[ORM\Column(length: 20)] private string $severity;
    #[ORM\Column(length: 30)] private string $status = 'submitted';
    #[ORM\Column(length: 500, nullable: true)] private ?string $pageUrl;
    #[ORM\Column(type: 'json')] private array $attachments = [];
    #[ORM\Column(type: 'datetime_immutable')] private \DateTimeImmutable $createdAt;
    #[ORM\Column(type: 'datetime_immutable')] private \DateTimeImmutable $updatedAt;
    public function __construct(User $reporter, ?BetaCampaign $campaign, string $title, string $description, ?string $expectedBehavior, ?string $actualBehavior, string $severity, ?string $pageUrl, array $attachments = []) { $this->reporter=$reporter; $this->campaign=$campaign; $this->title=$title; $this->description=$description; $this->expectedBehavior=$expectedBehavior; $this->actualBehavior=$actualBehavior; $this->severity=$severity; $this->pageUrl=$pageUrl; $this->attachments=$attachments; $this->createdAt=new \DateTimeImmutable(); $this->updatedAt=$this->createdAt; }
    public function getId(): ?int { return $this->id; } public function getReporter(): User { return $this->reporter; } public function getCampaign(): ?BetaCampaign { return $this->campaign; } public function getTitle(): string { return $this->title; } public function getDescription(): string { return $this->description; } public function getExpectedBehavior(): ?string { return $this->expectedBehavior; } public function getActualBehavior(): ?string { return $this->actualBehavior; } public function getSeverity(): string { return $this->severity; } public function getStatus(): string { return $this->status; } public function setStatus(string $status): self { $this->status=$status; $this->updatedAt=new \DateTimeImmutable(); return $this; } public function getPageUrl(): ?string { return $this->pageUrl; } public function getAttachments(): array { return $this->attachments; } public function getCreatedAt(): \DateTimeImmutable { return $this->createdAt; }
}
