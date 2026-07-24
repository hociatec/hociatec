<?php

declare(strict_types=1);

namespace App\Module\Admin\Quote\Service;

use App\Module\Admin\Quote\DTO\QuoteServiceFormData;
use App\Module\Quote\Entity\Service;
use Doctrine\ORM\EntityManagerInterface;

final readonly class QuoteServiceCatalogManager
{
    public function __construct(private EntityManagerInterface $entityManager)
    {
    }

    public function create(QuoteServiceFormData $data): Service
    {
        $this->validate($data, true);
        $service = new Service($data->title, $data->priceCents ?? 0, $data->vatRateBps ?? 0);
        $this->apply($service, $data);
        $this->entityManager->persist($service);
        $this->entityManager->flush();

        return $service;
    }

    public function update(Service $service, QuoteServiceFormData $data): Service
    {
        $this->validate($data, false);
        $this->apply($service, $data);
        $this->entityManager->flush();

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
