<?php

declare(strict_types=1);

namespace App\Module\Admin\Application\BetaTest\Service;

use App\Module\BetaTest\Domain\Entity\BetaCampaign;

final readonly class BetaCampaignPayloadMapper
{
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
        $this->assertChronology($startsAt, $endsAt);

        $campaign = new BetaCampaign($name, $description, $startsAt, $endsAt);
        $campaign->setStatus($this->status($payload['status'] ?? 'draft'));

        return $campaign;
    }

    /** @param array<string, mixed> $payload */
    public function update(BetaCampaign $campaign, array $payload): void
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

        $this->assertChronology($campaign->getStartsAt(), $campaign->getEndsAt());
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

    private function assertChronology(?\DateTimeImmutable $startsAt, ?\DateTimeImmutable $endsAt): void
    {
        if (null !== $startsAt && null !== $endsAt && $endsAt < $startsAt) {
            throw new \InvalidArgumentException('La date de fin doit être postérieure à la date de début.');
        }
    }
}
