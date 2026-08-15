<?php

declare(strict_types=1);

namespace App\Module\User\Domain\Entity\Concern;

trait UserCommunicationConcern
{
    public function getAccountNotificationsSeenSignature(): ?string
    {
        return $this->communication->accountNotificationsSeenSignature();
    }

    public function setAccountNotificationsSeenSignature(?string $signature): self
    {
        $this->communication->changeAccountNotificationsSeenSignature($signature);

        return $this;
    }

    /**
     * @return list<string>
     */
    public function getCommunicationPreferences(): array
    {
        return $this->communication->communicationPreferences();
    }

    /**
     * @param list<string> $preferences
     */
    public function setCommunicationPreferences(array $preferences): self
    {
        $this->communication->changeCommunicationPreferences($preferences);

        return $this;
    }
}
