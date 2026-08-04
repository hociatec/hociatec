<?php

declare(strict_types=1);

namespace App\Module\Admin\Application\BetaTest\Service;

use App\Infrastructure\Persistence\DoctrinePersistence;
use App\Module\BetaTest\Domain\Entity\BetaCampaign;

final readonly class AdminBetaCampaignManager
{
    public function __construct(private DoctrinePersistence $persistence)
    {
    }

    /** @param array<string, mixed> $payload */
    public function create(array $payload): BetaCampaign
    {
        $name = trim((string) ($payload['name'] ?? ''));
        $description = trim((string) ($payload['description'] ?? ''));
        if ('' === $name || '' === $description) {
            throw new \InvalidArgumentException('Le nom et la description sont obligatoires.');
        }

        $startsAt = $this->dateFromPayload($payload['startsAt'] ?? null) ?? new \DateTimeImmutable('today');
        $endsAt = $this->dateFromPayload($payload['endsAt'] ?? null) ?? $startsAt->modify('+30 days');
        if ($endsAt < $startsAt) {
            throw new \InvalidArgumentException('La date de fin doit être postérieure à la date de début.');
        }

        $campaign = new BetaCampaign($name, $description, $startsAt, $endsAt);
        $campaign->setStatus($this->status($payload['status'] ?? 'draft'));
        $this->persistence->persist($campaign);
        $this->persistence->flush();

        return $campaign;
    }

    /** @param array<string, mixed> $payload */
    public function update(BetaCampaign $campaign, array $payload): BetaCampaign
    {
        if (isset($payload['name'])) {
            $name = trim((string) $payload['name']);
            if ('' === $name) {
                throw new \InvalidArgumentException('Le nom est obligatoire.');
            }
            $campaign->setName($name);
        }

        if (isset($payload['description'])) {
            $description = trim((string) $payload['description']);
            if ('' === $description) {
                throw new \InvalidArgumentException('La description est obligatoire.');
            }
            $campaign->setDescription($description);
        }

        if (isset($payload['status'])) {
            $campaign->setStatus($this->status($payload['status']));
        }

        if (array_key_exists('startsAt', $payload)) {
            $campaign->setStartsAt($this->dateFromPayload($payload['startsAt']));
        }

        if (array_key_exists('endsAt', $payload)) {
            $campaign->setEndsAt($this->dateFromPayload($payload['endsAt']));
        }

        if (null !== $campaign->getStartsAt() && null !== $campaign->getEndsAt() && $campaign->getEndsAt() < $campaign->getStartsAt()) {
            throw new \InvalidArgumentException('La date de fin doit être postérieure à la date de début.');
        }

        $this->persistence->flush();

        return $campaign;
    }

    /** @param list<BetaCampaign> $campaigns */
    public function closeElapsedCampaigns(array $campaigns, \DateTimeImmutable $now): void
    {
        $hasClosedCampaign = false;
        foreach ($campaigns as $campaign) {
            if ('closed' === $campaign->getEffectiveStatus($now) && 'closed' !== $campaign->getStatus()) {
                $campaign->setStatus('closed');
                $hasClosedCampaign = true;
            }
        }

        if ($hasClosedCampaign) {
            $this->persistence->flush();
        }
    }

    public function delete(BetaCampaign $campaign): void
    {
        $this->persistence->remove($campaign);
        $this->persistence->flush();
    }

    private function status(mixed $value): string
    {
        return in_array($value, ['draft', 'active', 'closed'], true) ? (string) $value : 'draft';
    }

    private function dateFromPayload(mixed $value): ?\DateTimeImmutable
    {
        if (!is_string($value) || '' === trim($value)) {
            return null;
        }

        $date = \DateTimeImmutable::createFromFormat('!Y-m-d', trim($value));

        return $date instanceof \DateTimeImmutable ? $date : null;
    }
}
