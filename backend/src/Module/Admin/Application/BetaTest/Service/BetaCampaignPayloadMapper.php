<?php

declare(strict_types=1);

namespace App\Module\Admin\Application\BetaTest\Service;

use App\Module\Admin\Application\BetaTest\DTO\CreateBetaCampaignInput;
use App\Module\Admin\Application\BetaTest\DTO\UpdateBetaCampaignInput;
use App\Module\BetaTest\Domain\Entity\BetaCampaign;

final readonly class BetaCampaignPayloadMapper
{
    public function create(CreateBetaCampaignInput $input): BetaCampaign
    {
        $name = trim($input->name);
        $description = trim($input->description);
        if ('' === $name || '' === $description) {
            throw new \InvalidArgumentException('Le nom et la description sont obligatoires.');
        }

        $startsAt = $input->startsAt ?? new \DateTimeImmutable('today');
        $endsAt = $input->endsAt ?? $startsAt->modify('+30 days');
        $this->assertChronology($startsAt, $endsAt);

        $campaign = new BetaCampaign($name, $description, $startsAt, $endsAt);
        $campaign->setStatus($input->status);

        return $campaign;
    }

    public function update(BetaCampaign $campaign, UpdateBetaCampaignInput $input): void
    {
        if (null !== $input->name) {
            $name = trim($input->name);
            if ('' === $name) {
                throw new \InvalidArgumentException('Le nom est obligatoire.');
            }
            $campaign->setName($name);
        }

        if (null !== $input->description) {
            $description = trim($input->description);
            if ('' === $description) {
                throw new \InvalidArgumentException('La description est obligatoire.');
            }
            $campaign->setDescription($description);
        }

        if (null !== $input->status) {
            $campaign->setStatus($input->status);
        }

        if ($input->hasStartsAt) {
            $campaign->setStartsAt($input->startsAt);
        }

        if ($input->hasEndsAt) {
            $campaign->setEndsAt($input->endsAt);
        }

        $this->assertChronology($campaign->getStartsAt(), $campaign->getEndsAt());
    }

    private function assertChronology(?\DateTimeImmutable $startsAt, ?\DateTimeImmutable $endsAt): void
    {
        if (null !== $startsAt && null !== $endsAt && $endsAt < $startsAt) {
            throw new \InvalidArgumentException('La date de fin doit être postérieure à la date de début.');
        }
    }
}
