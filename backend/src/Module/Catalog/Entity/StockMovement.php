<?php

declare(strict_types=1);

namespace App\Module\Catalog\Entity;

use App\Module\Catalog\Repository\StockMovementRepository;
use App\Module\User\Entity\User;
use DateTimeImmutable;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: StockMovementRepository::class)]
#[ORM\Table(name: 'stock_movements')]
class StockMovement
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\ManyToOne]
    #[ORM\JoinColumn(nullable: false, onDelete: 'CASCADE')]
    private Product $product;

    #[ORM\Column(type: 'integer')]
    private int $delta;

    #[ORM\Column(type: 'integer')]
    private int $stockBefore;

    #[ORM\Column(type: 'integer')]
    private int $stockAfter;

    #[ORM\Column(length: 60)]
    private string $reason;

    #[ORM\Column(type: 'text', nullable: true)]
    private ?string $note = null;

    #[ORM\ManyToOne]
    #[ORM\JoinColumn(nullable: true, onDelete: 'SET NULL')]
    private ?User $actor = null;

    #[ORM\Column(type: 'datetime_immutable')]
    private DateTimeImmutable $createdAt;

    public function __construct(Product $product, int $delta, int $stockBefore, int $stockAfter, string $reason, ?User $actor = null)
    {
        $this->product = $product;
        $this->delta = $delta;
        $this->stockBefore = $stockBefore;
        $this->stockAfter = $stockAfter;
        $this->reason = trim($reason) !== '' ? trim($reason) : 'adjustment';
        $this->actor = $actor;
        $this->createdAt = new DateTimeImmutable();
    }

    public function getId(): ?int { return $this->id; }
    public function getProduct(): Product { return $this->product; }
    public function getDelta(): int { return $this->delta; }
    public function getStockBefore(): int { return $this->stockBefore; }
    public function getStockAfter(): int { return $this->stockAfter; }
    public function getReason(): string { return $this->reason; }
    public function getNote(): ?string { return $this->note; }
    public function setNote(?string $note): self { $this->note = $note !== null ? trim($note) : null; return $this; }
    public function getActor(): ?User { return $this->actor; }
    public function getCreatedAt(): DateTimeImmutable { return $this->createdAt; }
}
