<?php

declare(strict_types=1);

namespace App\Module\Admin\Quote\Service;

use App\Module\Admin\Quote\DTO\QuoteServiceFormData;
use App\Module\Quote\Entity\Service;
use Symfony\Component\HttpFoundation\File\UploadedFile;
use Symfony\Component\HttpFoundation\Request;

final class QuoteServiceFormMapper
{
    private const BILLING_MODES = [
        'prix fixe', 'heure', 'jour', 'intervention', 'audit', 'installation', 'maintenance',
    ];

    public function create(Request $request): QuoteServiceFormData
    {
        return $this->map($request, null);
    }

    public function update(Request $request, Service $service): QuoteServiceFormData
    {
        return $this->map($request, $service);
    }

    private function map(Request $request, ?Service $service): QuoteServiceFormData
    {
        $updatesDuration = null === $service
            || $request->request->has('durationValue')
            || $request->request->has('durationUnit');

        return new QuoteServiceFormData(
            trim((string) $request->request->get('title', $service?->getTitle() ?? '')),
            $this->optionalString($request->request->get('description', $service?->getDescription())),
            $this->billingMode($request->request->get('unit', $service?->getUnit())),
            $this->durationValue($request->request->get('durationValue', $service?->getDurationValue())),
            $this->durationUnit($request->request->get('durationUnit', $service?->getDurationUnit())),
            $request->request->has('price') || null === $service
                ? $this->priceToCents($request->request->get('price'))
                : null,
            $request->request->has('vatRate') || null === $service
                ? $this->vatToBps($request->request->get('vatRate'))
                : null,
            $this->boolean($request->request->get('isFeaturedHome', $service?->isFeaturedHome() ?? false)),
            $this->imageFile($request->files->get('image')),
            $this->optionalString($request->request->get('imageUrl', $service?->getImageExternalUrl())),
            $this->optionalString($request->request->get('imageAlt', $service?->getImageAlt())),
            null === $service || $request->request->has('unit'),
            $updatesDuration,
        );
    }

    private function billingMode(mixed $value): ?string
    {
        if (!is_string($value) || '' === trim($value)) {
            return 'prix fixe';
        }

        $normalized = mb_strtolower(trim($value));

        return in_array($normalized, self::BILLING_MODES, true) ? $normalized : null;
    }

    private function priceToCents(mixed $value): int
    {
        if (is_int($value) || is_float($value)) {
            return (int) round($value * 100);
        }
        if (is_string($value)) {
            $normalized = str_replace(',', '.', $value);
            if (is_numeric($normalized)) {
                return (int) round((float) $normalized * 100);
            }
        }

        return -1;
    }

    private function vatToBps(mixed $value): int
    {
        if (null === $value || '' === $value) {
            return 0;
        }

        return (int) round(((float) str_replace(',', '.', (string) $value)) * 100);
    }

    private function durationValue(mixed $value): ?int
    {
        if (null === $value || '' === $value) {
            return null;
        }

        $duration = (int) $value;

        return $duration > 0 ? $duration : null;
    }

    private function durationUnit(mixed $value): ?string
    {
        return is_string($value) && in_array($value, ['hour', 'day'], true) ? $value : null;
    }

    private function optionalString(mixed $value): ?string
    {
        return is_string($value) && '' !== trim($value) ? trim($value) : null;
    }

    private function boolean(mixed $value): bool
    {
        if (is_bool($value)) {
            return $value;
        }

        return in_array((string) $value, ['1', 'true', 'on', 'yes'], true);
    }

    private function imageFile(mixed $value): ?UploadedFile
    {
        return $value instanceof UploadedFile ? $value : null;
    }
}
