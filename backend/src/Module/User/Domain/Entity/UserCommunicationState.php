<?php

declare(strict_types=1);

namespace App\Module\User\Domain\Entity;

use Doctrine\ORM\Mapping as ORM;

#[ORM\Embeddable]
final class UserCommunicationState
{
    #[ORM\Column(type: 'text', nullable: true)]
    private ?string $accountNotificationsSeenSignature = null;

    /** @var list<string>|null */
    #[ORM\Column(type: 'json', nullable: true)]
    private ?array $communicationPreferences = null;

    public function accountNotificationsSeenSignature(): ?string
    {
        return $this->accountNotificationsSeenSignature;
    }

    public function changeAccountNotificationsSeenSignature(?string $signature): void
    {
        $signature = null !== $signature ? trim($signature) : null;
        $this->accountNotificationsSeenSignature = '' !== $signature ? $signature : null;
    }

    /** @return list<string> */
    public function communicationPreferences(): array
    {
        $preferences = is_array($this->communicationPreferences)
            ? $this->communicationPreferences
            : ['notification', 'email'];

        return array_values(array_unique(array_filter(
            $preferences,
            static fn (string $preference): bool => in_array($preference, ['notification', 'email', 'news_email', 'phone'], true),
        )));
    }

    /** @param list<string> $preferences */
    public function changeCommunicationPreferences(array $preferences): void
    {
        $this->communicationPreferences = array_values(array_unique(array_filter(
            $preferences,
            static fn (string $preference): bool => in_array($preference, ['notification', 'email', 'news_email', 'phone'], true),
        )));
    }
}
