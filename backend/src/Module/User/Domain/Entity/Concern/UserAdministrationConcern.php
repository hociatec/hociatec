<?php

declare(strict_types=1);

namespace App\Module\User\Domain\Entity\Concern;

trait UserAdministrationConcern
{
    public function getAdminNotes(): ?string
    {
        return $this->administration->adminNotes();
    }

    public function setAdminNotes(?string $adminNotes): self
    {
        $this->administration->changeAdminNotes($adminNotes);

        return $this;
    }

    /**
     * @return list<string>
     */
    public function getAdminTags(): array
    {
        return $this->administration->adminTags();
    }

    /**
     * @param list<string> $adminTags
     */
    public function setAdminTags(array $adminTags): self
    {
        $this->administration->changeAdminTags($adminTags);

        return $this;
    }

    public function getLoyaltyPointsBalance(): int
    {
        return $this->administration->loyaltyPointsBalance();
    }

    public function setLoyaltyPointsBalance(int $loyaltyPointsBalance): self
    {
        $this->administration->changeLoyaltyPointsBalance($loyaltyPointsBalance);

        return $this;
    }

    public function addLoyaltyPoints(int $points): self
    {
        $this->administration->addLoyaltyPoints($points);

        return $this;
    }
}
