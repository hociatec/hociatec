<?php

declare(strict_types=1);

namespace App\Module\Order\Domain\Entity;

use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity]
#[ORM\Table(name: 'order_events')]
class OrderEvent
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\ManyToOne]
    #[ORM\JoinColumn(nullable: false, onDelete: 'CASCADE')]
    private Order $order;

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

    public function __construct(Order $order, string $type, ?string $message, ?int $actorUserId, ?string $actorName)
    {
        $this->order = $order;
        $this->type = $type;
        $this->message = $message;
        $this->actorUserId = $actorUserId;
        $this->actorName = $actorName;
        $this->createdAt = new \DateTimeImmutable();
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getOrder(): Order
    {
        return $this->order;
    }

    public function getType(): string
    {
        return $this->type;
    }

    public function getMessage(): ?string
    {
        return $this->message;
    }

    public function getActorUserId(): ?int
    {
        return $this->actorUserId;
    }

    public function getActorName(): ?string
    {
        return $this->actorName;
    }

    public function getCreatedAt(): \DateTimeImmutable
    {
        return $this->createdAt;
    }
}
