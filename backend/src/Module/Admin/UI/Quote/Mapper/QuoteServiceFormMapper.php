<?php

declare(strict_types=1);

namespace App\Module\Admin\UI\Quote\Mapper;

use App\Module\Admin\Application\Quote\DTO\QuoteServiceFormData;
use App\Module\Service\Domain\Entity\ServiceOffering;
use App\Module\Service\Domain\Enum\ServiceBillingMode;
use App\Shared\Domain\ValueObject\DecimalNumber;
use Symfony\Component\HttpFoundation\File\UploadedFile;
use Symfony\Component\HttpFoundation\Request;

final class QuoteServiceFormMapper
{
    public function create(Request $request): QuoteServiceFormData
    {
        return $this->map($request, null);
    }

    public function update(Request $request, ServiceOffering $service): QuoteServiceFormData
    {
        return $this->map($request, $service);
    }

    private function map(Request $request, ?ServiceOffering $service): QuoteServiceFormData
    {
        $updatesDuration = null === $service
            || $request->request->has('durationValue')
            || $request->request->has('durationUnit');

        return new QuoteServiceFormData([
            'title' => trim((string) $request->request->get('title', $service?->getTitle() ?? '')),
            'description' => $this->optionalString($request->request->get('description', $service?->getDescription())),
            'billingMode' => $this->billingMode($request->request->get('unit', $service?->getUnit())),
            'durationValue' => $this->durationValue($request->request->get('durationValue', $service?->getDurationValue())),
            'durationUnit' => $this->durationUnit($request->request->get('durationUnit', $service?->getDurationUnit())),
            'priceCents' => $request->request->has('price') || null === $service
                ? $this->priceToCents($request->request->get('price'))
                : null,
            'vatRateBps' => $request->request->has('vatRate') || null === $service
                ? $this->vatToBps($request->request->get('vatRate'))
                : null,
            'isFeaturedHome' => $this->boolean($request->request->get('isFeaturedHome', $service?->isFeaturedHome() ?? false)),
            'imageFile' => $this->imageFile($request->files->get('image')),
            'imageUrl' => $this->optionalString($request->request->get('imageUrl', $service?->getImageExternalUrl())),
            'imageAlt' => $this->optionalString($request->request->get('imageAlt', $service?->getImageAlt())),
            'updatesBillingMode' => null === $service || $request->request->has('unit'),
            'updatesDuration' => $updatesDuration,
        ]);
    }

    private function billingMode(mixed $value): ?string
    {
        return ServiceBillingMode::normalize($value)?->value;
    }

    private function priceToCents(mixed $value): int
    {
        return DecimalNumber::toScaledInt($value, 2) ?? -1;
    }

    private function vatToBps(mixed $value): int
    {
        if (null === $value || '' === $value) {
            return 0;
        }

        return DecimalNumber::toScaledInt($value, 2) ?? 0;
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
