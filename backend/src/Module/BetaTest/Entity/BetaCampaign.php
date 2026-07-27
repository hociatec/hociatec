<?php

declare(strict_types=1);

namespace App\Module\BetaTest\Entity;

use App\Module\BetaTest\Repository\BetaCampaignRepository;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: BetaCampaignRepository::class)]
#[ORM\Table(name: 'beta_campaigns')]
class BetaCampaign
{
    #[ORM\Id, ORM\GeneratedValue, ORM\Column] private ?int $id = null;
    #[ORM\Column(length: 120)] private string $name;
    #[ORM\Column(type: 'text')] private string $description;
    #[ORM\Column(length: 30)] private string $status = 'draft';
    #[ORM\Column(type: 'datetime_immutable', nullable: true)] private ?\DateTimeImmutable $startsAt;
    #[ORM\Column(type: 'datetime_immutable', nullable: true)] private ?\DateTimeImmutable $endsAt;
    #[ORM\Column(type: 'datetime_immutable')] private \DateTimeImmutable $createdAt;
    public function __construct(string $name, string $description, ?\DateTimeImmutable $startsAt = null, ?\DateTimeImmutable $endsAt = null)
    {
        $this->name = $name;
        $this->description = $description;
        $this->startsAt = $startsAt;
        $this->endsAt = $endsAt;
        $this->createdAt = new \DateTimeImmutable();
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getName(): string
    {
        return $this->name;
    }

    public function getDescription(): string
    {
        return $this->description;
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

    public function getStartsAt(): ?\DateTimeImmutable
    {
        return $this->startsAt;
    }

    public function getEndsAt(): ?\DateTimeImmutable
    {
        return $this->endsAt;
    }

    public function getCreatedAt(): \DateTimeImmutable
    {
        return $this->createdAt;
    }
}
