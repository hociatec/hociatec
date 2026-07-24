<?php

declare(strict_types=1);

namespace App\Module\Audit\Entity;

use App\Module\Audit\Repository\AuditChecklistItemRepository;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: AuditChecklistItemRepository::class)]
#[ORM\Table(name: 'audit_checklist_items')]
class AuditChecklistItem
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\ManyToOne(inversedBy: 'items')]
    #[ORM\JoinColumn(nullable: false, onDelete: 'CASCADE')]
    private ?AuditRequest $audit = null;

    #[ORM\Column(length: 100)]
    private string $category;

    #[ORM\Column(length: 100)]
    private string $criterionKey;

    #[ORM\Column(length: 255)]
    private string $label;

    #[ORM\Column(type: 'integer')]
    private int $position = 0;

    #[ORM\Column(length: 10, nullable: true)]
    private ?string $level = null; // e.g. WCAG A/AA/AAA

    #[ORM\Column(type: 'boolean', nullable: true)]
    private ?bool $isCompliant = null;

    #[ORM\Column(type: 'text', nullable: true)]
    private ?string $comment = null;

    public function __construct(string $category, string $criterionKey, string $label, int $position)
    {
        $this->category = $category;
        $this->criterionKey = $criterionKey;
        $this->label = $label;
        $this->position = $position;
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getAudit(): ?AuditRequest
    {
        return $this->audit;
    }

    public function setAudit(?AuditRequest $audit): self
    {
        $this->audit = $audit;

        return $this;
    }

    public function getCategory(): string
    {
        return $this->category;
    }

    public function getCriterionKey(): string
    {
        return $this->criterionKey;
    }

    public function getLabel(): string
    {
        return $this->label;
    }

    public function getPosition(): int
    {
        return $this->position;
    }

    public function getIsCompliant(): ?bool
    {
        return $this->isCompliant;
    }

    public function setIsCompliant(?bool $value): self
    {
        $this->isCompliant = $value;

        return $this;
    }

    public function getComment(): ?string
    {
        return $this->comment;
    }

    public function setComment(?string $comment): self
    {
        $this->comment = $comment;

        return $this;
    }

    public function getLevel(): ?string
    {
        return $this->level;
    }

    public function setLevel(?string $level): self
    {
        $this->level = $level;

        return $this;
    }
}
