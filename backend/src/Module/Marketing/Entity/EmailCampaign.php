<?php

declare(strict_types=1);

namespace App\Module\Marketing\Entity;

use App\Module\Marketing\Repository\EmailCampaignRepository;
use DateTimeImmutable;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: EmailCampaignRepository::class)]
#[ORM\Table(name: 'marketing_email_campaigns')]
#[ORM\HasLifecycleCallbacks]
class EmailCampaign
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(length: 140)]
    private string $name;

    #[ORM\Column(length: 60)]
    private string $segmentKey;

    /** @var array<string, mixed> */
    #[ORM\Column(type: 'json')]
    private array $criteria = [];

    #[ORM\ManyToOne(targetEntity: EmailTemplate::class)]
    #[ORM\JoinColumn(nullable: true, onDelete: 'SET NULL')]
    private ?EmailTemplate $template = null;

    #[ORM\Column(length: 180)]
    private string $subjectSnapshot;

    #[ORM\Column(type: 'text')]
    private string $htmlSnapshot;

    #[ORM\Column(type: 'text', nullable: true)]
    private ?string $textSnapshot = null;

    #[ORM\Column(type: 'integer')]
    private int $recipientsCount = 0;

    #[ORM\Column(length: 180, nullable: true)]
    private ?string $createdByEmail = null;

    #[ORM\Column(type: 'datetime_immutable')]
    private DateTimeImmutable $sentAt;

    #[ORM\Column(type: 'datetime_immutable')]
    private DateTimeImmutable $createdAt;

    #[ORM\Column(type: 'datetime_immutable')]
    private DateTimeImmutable $updatedAt;

    /**
     * @param array<string, mixed> $criteria
     */
    public function __construct(
        string $name,
        string $segmentKey,
        array $criteria,
        string $subjectSnapshot,
        string $htmlSnapshot,
        ?string $textSnapshot,
        int $recipientsCount,
        ?string $createdByEmail,
        ?EmailTemplate $template = null,
    ) {
        $this->name = $name;
        $this->segmentKey = $segmentKey;
        $this->criteria = $criteria;
        $this->subjectSnapshot = $subjectSnapshot;
        $this->htmlSnapshot = $htmlSnapshot;
        $this->textSnapshot = $textSnapshot;
        $this->recipientsCount = max(0, $recipientsCount);
        $this->createdByEmail = $createdByEmail;
        $this->template = $template;
        $now = new DateTimeImmutable();
        $this->sentAt = $now;
        $this->createdAt = $now;
        $this->updatedAt = $now;
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getName(): string
    {
        return $this->name;
    }

    public function getSegmentKey(): string
    {
        return $this->segmentKey;
    }

    /**
     * @return array<string, mixed>
     */
    public function getCriteria(): array
    {
        return $this->criteria;
    }

    public function getTemplate(): ?EmailTemplate
    {
        return $this->template;
    }

    public function getSubjectSnapshot(): string
    {
        return $this->subjectSnapshot;
    }

    public function getHtmlSnapshot(): string
    {
        return $this->htmlSnapshot;
    }

    public function getTextSnapshot(): ?string
    {
        return $this->textSnapshot;
    }

    public function getRecipientsCount(): int
    {
        return $this->recipientsCount;
    }

    public function getCreatedByEmail(): ?string
    {
        return $this->createdByEmail;
    }

    public function getSentAt(): DateTimeImmutable
    {
        return $this->sentAt;
    }

    public function getCreatedAt(): DateTimeImmutable
    {
        return $this->createdAt;
    }

    public function getUpdatedAt(): DateTimeImmutable
    {
        return $this->updatedAt;
    }

    #[ORM\PreUpdate]
    public function touch(): void
    {
        $this->updatedAt = new DateTimeImmutable();
    }
}
