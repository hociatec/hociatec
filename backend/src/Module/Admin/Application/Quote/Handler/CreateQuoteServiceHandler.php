<?php

declare(strict_types=1);

namespace App\Module\Admin\Application\Quote\Handler;

use App\Module\Admin\Application\Quote\Applier\QuoteServiceFormApplier;
use App\Module\Admin\Application\Quote\DTO\QuoteServiceFormData;
use App\Module\Quote\Domain\Entity\ServiceOffering;
use App\Module\Quote\Domain\Exception\QuoteOperationException;
use App\Shared\Application\UnitOfWork;

final readonly class CreateQuoteServiceHandler
{
    public function __construct(
        private UnitOfWork $persistence,
        private QuoteServiceFormApplier $formApplier,
    ) {
    }

    public function create(QuoteServiceFormData $data): ServiceOffering
    {
        $this->formApplier->validate($data, true);
        $service = new ServiceOffering($data->title, $data->priceCents ?? 0, $data->vatRateBps ?? 0);
        $this->formApplier->apply($service, $data);
        try {
            $this->persistence->persist($service);
            $this->persistence->commit();
        } catch (\RuntimeException $exception) {
            throw QuoteOperationException::failed('Impossible de créer le service.', $exception);
        }

        return $service;
    }
}
