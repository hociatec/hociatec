<?php

declare(strict_types=1);

namespace App\Module\Notification\Domain\Entity;

use App\Module\User\Domain\Entity\User;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity]
#[ORM\Table(name: 'account_notification_events')]
#[ORM\Index(name: 'IDX_ACCOUNT_NOTIFICATION_USER', columns: ['user_id'])]
#[ORM\UniqueConstraint(name: 'UNIQ_ACCOUNT_NOTIFICATION_USER_KEY', columns: ['user_id', 'notification_key'])]
class AccountNotificationEvent
{
    #[ORM\Id, ORM\GeneratedValue, ORM\Column]
    private ?int $id = null;

    #[ORM\ManyToOne(targetEntity: User::class)]
    #[ORM\JoinColumn(name: 'user_id', referencedColumnName: 'id', nullable: false, onDelete: 'CASCADE')]
    private User $user;

    #[ORM\Column(name: 'notification_key', length: 190)]
    private string $key;

    #[ORM\Column(length: 180)]
    private string $title;

    #[ORM\Column(type: 'text')]
    private string $message;

    #[ORM\Column(name: 'target_url', length: 500)]
    private string $targetUrl;

    #[ORM\Column(length: 60)]
    private string $type;

    #[ORM\Column(type: 'datetime_immutable')]
    private \DateTimeImmutable $createdAt;

    public function __construct(User $user, string $key, string $title, string $message, string $targetUrl, string $type)
    {
        $this->user = $user;
        $this->key = $key;
        $this->title = $title;
        $this->message = $message;
        $this->targetUrl = $targetUrl;
        $this->type = $type;
        $this->createdAt = new \DateTimeImmutable();
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getKey(): string
    {
        return $this->key;
    }

    public function getTitle(): string
    {
        return $this->title;
    }

    public function getMessage(): string
    {
        return $this->message;
    }

    public function getTargetUrl(): string
    {
        return $this->targetUrl;
    }

    public function getType(): string
    {
        return $this->type;
    }

    public function getCreatedAt(): \DateTimeImmutable
    {
        return $this->createdAt;
    }
}
