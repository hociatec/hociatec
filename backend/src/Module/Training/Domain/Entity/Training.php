<?php

declare(strict_types=1);

namespace App\Module\Training\Domain\Entity;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity]
#[ORM\Table(name: 'trainings')]
#[ORM\UniqueConstraint(name: 'UNIQ_TRAININGS_SLUG', fields: ['slug'])]
#[ORM\HasLifecycleCallbacks]
class Training
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(length: 180)]
    private string $title;

    #[ORM\Column(length: 190)]
    private string $slug;

    #[ORM\Column(type: 'text', nullable: true)]
    private ?string $shortDescription = null;

    #[ORM\Column(type: 'text', nullable: true)]
    private ?string $objective = null;

    #[ORM\Column(type: 'text', nullable: true)]
    private ?string $audience = null;

    #[ORM\Column(length: 80, options: ['default' => 'general'])]
    private string $category = 'general';

    #[ORM\Column(type: 'integer')]
    private int $durationMinutes;

    #[ORM\Column(type: 'integer')]
    private int $priceCents;

    /** @var list<string> */
    #[ORM\Column(type: 'json')]
    private array $availableFormats = ['onsite', 'remote'];

    #[ORM\Column(type: 'boolean', options: ['default' => true])]
    private bool $isActive = true;

    #[ORM\Column(type: 'datetime_immutable')]
    private \DateTimeImmutable $createdAt;

    #[ORM\Column(type: 'datetime_immutable')]
    private \DateTimeImmutable $updatedAt;

    /** @var Collection<int, TrainingRoadmapItem> */
    #[ORM\OneToMany(mappedBy: 'training', targetEntity: TrainingRoadmapItem::class, cascade: ['persist', 'remove'], orphanRemoval: true)]
    #[ORM\OrderBy(['position' => 'ASC'])]
    private Collection $roadmapItems;

    public function __construct(string $title, string $slug, int $durationMinutes, int $priceCents)
    {
        $this->title = $title;
        $this->slug = $slug;
        $this->durationMinutes = $durationMinutes;
        $this->priceCents = $priceCents;
        $this->roadmapItems = new ArrayCollection();
        $now = new \DateTimeImmutable();
        $this->createdAt = $now;
        $this->updatedAt = $now;
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getTitle(): string
    {
        return $this->title;
    }

    public function setTitle(string $title): self
    {
        $this->title = $title;

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

    public function getShortDescription(): ?string
    {
        return $this->shortDescription;
    }

    public function setShortDescription(?string $shortDescription): self
    {
        $this->shortDescription = $shortDescription;

        return $this;
    }

    public function getObjective(): ?string
    {
        return $this->objective;
    }

    public function setObjective(?string $objective): self
    {
        $this->objective = $objective;

        return $this;
    }

    public function getAudience(): ?string
    {
        return $this->audience;
    }

    public function setAudience(?string $audience): self
    {
        $this->audience = $audience;

        return $this;
    }

    public function getCategory(): string
    {
        return $this->category;
    }

    public function setCategory(string $category): self
    {
        $this->category = '' !== trim($category) ? trim($category) : 'general';

        return $this;
    }

    public function getDurationMinutes(): int
    {
        return $this->durationMinutes;
    }

    public function setDurationMinutes(int $durationMinutes): self
    {
        $this->durationMinutes = $durationMinutes;

        return $this;
    }

    public function getPriceCents(): int
    {
        return $this->priceCents;
    }

    public function setPriceCents(int $priceCents): self
    {
        $this->priceCents = $priceCents;

        return $this;
    }

    /** @return list<string> */
    public function getAvailableFormats(): array
    {
        return $this->availableFormats;
    }

    /** @param list<string> $availableFormats */
    public function setAvailableFormats(array $availableFormats): self
    {
        $this->availableFormats = $availableFormats;

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

    /** @return Collection<int, TrainingRoadmapItem> */
    public function getRoadmapItems(): Collection
    {
        return $this->roadmapItems;
    }

    public function addRoadmapItem(TrainingRoadmapItem $item): self
    {
        if (!$this->roadmapItems->contains($item)) {
            $this->roadmapItems->add($item);
            $item->setTraining($this);
        }

        return $this;
    }

    public function clearRoadmapItems(): self
    {
        $this->roadmapItems->clear();

        return $this;
    }

    #[ORM\PreUpdate]
    public function touch(): void
    {
        $this->updatedAt = new \DateTimeImmutable();
    }
}
