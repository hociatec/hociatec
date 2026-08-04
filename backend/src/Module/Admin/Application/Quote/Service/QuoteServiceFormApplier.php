<?php

declare(strict_types=1);

namespace App\Module\Admin\Application\Quote\Service;

use App\Module\Admin\Application\Quote\DTO\QuoteServiceFormData;
use App\Module\Quote\Domain\Entity\Service;

final readonly class QuoteServiceFormApplier
{
    public function validate(QuoteServiceFormData $data, bool $creating): void
    {
        if ('' === $data->title || ($creating && ($data->priceCents ?? -1) < 0)) {
            throw new \InvalidArgumentException('Titre ou prix invalide.');
        }
        if ($data->updatesBillingMode && null === $data->billingMode) {
            throw new \InvalidArgumentException('Mode de facturation invalide.');
        }
        if ($data->updatesDuration && ((null === $data->durationValue) !== (null === $data->durationUnit))) {
            throw new \InvalidArgumentException('La durée doit contenir une valeur et une unité.');
        }
        if (null !== $data->priceCents && $data->priceCents < 0) {
            throw new \InvalidArgumentException('Prix invalide.');
        }
    }

    public function apply(Service $service, QuoteServiceFormData $data): void
    {
        $service->setTitle($data->title)->setDescription($data->description);
        $service->setIsFeaturedHome($data->isFeaturedHome);
        $service->setImageAlt($data->imageAlt);
        if (null !== $data->imageFile) {
            $service->setImageFile($data->imageFile);
            $service->setImageExternalUrl(null);
        } else {
            $service->setImageExternalUrl($data->imageUrl);
        }
        if ($data->updatesBillingMode && null !== $data->billingMode) {
            $service->setUnit($data->billingMode);
        }
        if ($data->updatesDuration) {
            $service->setDurationValue($data->durationValue)->setDurationUnit($data->durationUnit);
        }
        if (null !== $data->priceCents) {
            $service->setPriceCents($data->priceCents);
        }
        if (null !== $data->vatRateBps) {
            $service->setVatRateBps($data->vatRateBps);
        }
    }
}
