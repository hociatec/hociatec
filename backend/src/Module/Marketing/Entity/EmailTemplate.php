<?php

declare(strict_types=1);

namespace App\Module\Marketing\Entity;

use App\Module\Marketing\Repository\EmailTemplateRepository;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: EmailTemplateRepository::class)]
#[ORM\Table(name: 'marketing_email_templates')]
#[ORM\HasLifecycleCallbacks]
class EmailTemplate
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(length: 120)]
    private string $name;

    #[ORM\Column(length: 140, unique: true)]
    private string $slug;

    #[ORM\Column(length: 50)]
    private string $scenarioKey;

    #[ORM\Column(length: 180)]
    private string $subjectTemplate;

    #[ORM\Column(type: 'text')]
    private string $htmlBody;

    #[ORM\Column(type: 'text', nullable: true)]
    private ?string $textBody = null;

    #[ORM\Column(options: ['default' => true])]
    private bool $isActive = true;

    #[ORM\Column(type: 'datetime_immutable')]
    private \DateTimeImmutable $createdAt;

    #[ORM\Column(type: 'datetime_immutable')]
    private \DateTimeImmutable $updatedAt;

    public function __construct(
        string $name,
        string $slug,
        string $scenarioKey,
        string $subjectTemplate,
        string $htmlBody,
        ?string $textBody = null,
    ) {
        $this->name = $name;
        $this->slug = $slug;
        $this->scenarioKey = $scenarioKey;
        $this->subjectTemplate = $subjectTemplate;
        $this->htmlBody = $htmlBody;
        $this->textBody = $textBody;
        $now = new \DateTimeImmutable();
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

    public function setName(string $name): self
    {
        $this->name = $name;

        return $this;
    }

    public function getSlug(): string
    {
        return $this->slug;
    }

    public function setSlug(string $slug): self
    {
        $this->slug = $slug;

        return $this;
    }

    public function getScenarioKey(): string
    {
        return $this->scenarioKey;
    }

    public function setScenarioKey(string $scenarioKey): self
    {
        $this->scenarioKey = $scenarioKey;

        return $this;
    }

    public function getSubjectTemplate(): string
    {
        return $this->subjectTemplate;
    }

    public function setSubjectTemplate(string $subjectTemplate): self
    {
        $this->subjectTemplate = $subjectTemplate;

        return $this;
    }

    public function getHtmlBody(): string
    {
        return $this->htmlBody;
    }

    public function setHtmlBody(string $htmlBody): self
    {
        $this->htmlBody = $htmlBody;

        return $this;
    }

    public function getTextBody(): ?string
    {
        return $this->textBody;
    }

    public function setTextBody(?string $textBody): self
    {
        $this->textBody = $textBody;

        return $this;
    }

    public function isActive(): bool
    {
        return $this->isActive;
    }

    public function setIsActive(bool $isActive): self
    {
        $this->isActive = $isActive;

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
