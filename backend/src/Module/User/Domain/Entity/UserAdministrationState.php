<?php

declare(strict_types=1);

namespace App\Module\User\Domain\Entity;

use Doctrine\ORM\Mapping as ORM;

#[ORM\Embeddable]
final class UserAdministrationState
{
    #[ORM\Column(type: 'text', nullable: true)]
    private ?string $adminNotes = null;

    /** @var list<string> */
    #[ORM\Column(type: 'json')]
    private array $adminTags = [];

    #[ORM\Column(type: 'integer', options: ['default' => 0])]
    private int $loyaltyPointsBalance = 0;

    public function adminNotes(): ?string
    {
        return $this->adminNotes;
    }

    public function changeAdminNotes(?string $notes): void
    {
        $this->adminNotes = null !== $notes ? trim($notes) : null;
    }

    /** @return list<string> */
    public function adminTags(): array
    {
        return array_values(array_filter(
            array_map(static fn (mixed $tag): string => trim((string) $tag), $this->adminTags),
            static fn (string $tag): bool => '' !== $tag,
        ));
    }

    /** @param list<string> $tags */
    public function changeAdminTags(array $tags): void
    {
        $normalized = [];
        foreach ($tags as $tag) {
            $value = trim((string) $tag);
            if ('' === $value) {
                continue;
            }

            $normalized[] = $value;
        }

        $this->adminTags = array_values(array_unique($normalized));
    }

    public function loyaltyPointsBalance(): int
    {
        return $this->loyaltyPointsBalance;
    }

    public function changeLoyaltyPointsBalance(int $points): void
    {
        if ($points < 0) {
            throw new \InvalidArgumentException('Le solde de points ne peut pas être négatif.');
        }

        $this->loyaltyPointsBalance = $points;
    }

    public function addLoyaltyPoints(int $points): void
    {
        if ($this->loyaltyPointsBalance + $points < 0) {
            throw new \InvalidArgumentException('Le solde de points ne peut pas devenir négatif.');
        }

        $this->loyaltyPointsBalance += $points;
    }
}
