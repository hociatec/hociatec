<?php

declare(strict_types=1);

namespace App\Module\Admin\Application\Quote\Handler;

use App\Module\Admin\Application\Quote\Applier\QuoteServiceFormApplier;
use App\Module\Admin\Application\Quote\DTO\QuoteServiceFormData;
use App\Module\Quote\Domain\Exception\QuoteOperationException;
use App\Module\Service\Domain\Entity\ServiceOffering;
use App\Shared\Application\UnitOfWork;

final readonly class UpdateQuoteServiceHandler
{
    public function __construct(
        private UnitOfWork $persistence,
        private QuoteServiceFormApplier $formApplier,
    ) {
    }

    public function update(ServiceOffering $service, QuoteServiceFormData $data): ServiceOffering
    {
        $this->formApplier->validate($data, false);
        $this->formApplier->apply($service, $data);
        try {
            $this->persistence->flush();
        } catch (\RuntimeException $exception) {
            throw QuoteOperationException::failed('Impossible de mettre à jour le service.', $exception);
        }

        return $service;
    }
}
