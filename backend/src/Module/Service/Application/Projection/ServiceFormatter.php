<?php

declare(strict_types=1);

namespace App\Module\Service\Application\Projection;

use App\Module\Service\Domain\Entity\ServiceOffering;

final class ServiceFormatter
{
    /**
     * @return array<string, mixed>
     */
    public function format(ServiceOffering $service): array
    {
        $durationValue = $service->getDurationValue();
        $durationUnit = $service->getDurationUnit();

        return [
            'id' => $service->getId(),
            'title' => $service->getTitle(),
            'description' => $service->getDescription(),
            'unit' => $service->getUnit(),
            'isFeaturedHome' => $service->isFeaturedHome(),
            'imageUrl' => $this->formatImageUrl($service),
            'imageAlt' => $service->getImageAlt(),
            'durationValue' => $durationValue,
            'durationUnit' => $durationUnit,
            'durationLabel' => $this->formatDurationLabel($durationValue, $durationUnit),
            'priceCents' => $service->getPriceCents(),
            'vatRate' => $service->getVatRateBps() / 100,
        ];
    }

    private function formatImageUrl(ServiceOffering $service): ?string
    {
        if (null !== $service->getImageName() && '' !== trim($service->getImageName())) {
            return sprintf('/uploads/services/%s', ltrim($service->getImageName(), '/'));
        }

        if (null !== $service->getImageExternalUrl() && '' !== trim($service->getImageExternalUrl())) {
            return trim($service->getImageExternalUrl());
        }

        return null;
    }

    private function formatDurationLabel(?int $durationValue, ?string $durationUnit): ?string
    {
        if (null === $durationValue || $durationValue <= 0 || null === $durationUnit || '' === $durationUnit) {
            return null;
        }

        if ('day' === $durationUnit) {
            return $durationValue.' '.($durationValue > 1 ? 'jours' : 'jour');
        }

        return $durationValue.' '.($durationValue > 1 ? 'heures' : 'heure');
    }
}
