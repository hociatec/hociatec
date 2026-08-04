<?php

declare(strict_types=1);

namespace App\Module\Admin\Application\Quote\Service;

use App\Infrastructure\Persistence\DoctrineUnitOfWork;
use App\Module\Admin\Application\Quote\DTO\QuoteServiceFormData;
use App\Module\Quote\Domain\Entity\Service;
use App\Module\Quote\Domain\Exception\QuoteOperationException;

final readonly class QuoteServiceCatalogManager
{
    public function __construct(private DoctrineUnitOfWork $persistence)
    {
    }

    public function create(QuoteServiceFormData $data): Service
    {
        $this->validate($data, true);
        $service = new Service($data->title, $data->priceCents ?? 0, $data->vatRateBps ?? 0);
        $this->apply($service, $data);
        try {
            $this->persistence->persist($service);
            $this->persistence->flush();
        } catch (\RuntimeException $exception) {
            throw QuoteOperationException::failed('Impossible de créer le service.', $exception);
        }

        return $service;
    }

    public function update(Service $service, QuoteServiceFormData $data): Service
    {
        $this->validate($data, false);
        $this->apply($service, $data);
        try {
            $this->persistence->flush();
        } catch (\RuntimeException $exception) {
            throw QuoteOperationException::failed('Impossible de mettre à jour le service.', $exception);
        }

        return $service;
    }

    private function validate(QuoteServiceFormData $data, bool $creating): void
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

    private function apply(Service $service, QuoteServiceFormData $data): void
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
