<?php

declare(strict_types=1);

namespace App\Module\Training\Domain\Entity;

use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity]
#[ORM\Table(name: 'training_roadmap_items')]
#[ORM\Index(name: 'IDX_TRAINING_ROADMAP_TRAINING', columns: ['training_id'])]
class TrainingRoadmapItem
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\ManyToOne(targetEntity: Training::class, inversedBy: 'roadmapItems')]
    #[ORM\JoinColumn(nullable: false, onDelete: 'CASCADE')]
    private Training $training;

    #[ORM\Column(type: 'integer')]
    private int $position;

    #[ORM\Column(length: 220)]
    private string $title;

    public function __construct(int $position, string $title)
    {
        $this->position = $position;
        $this->title = $title;
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

    public function getPosition(): int
    {
        return $this->position;
    }

    public function setPosition(int $position): self
    {
        $this->position = $position;

        return $this;
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
}
