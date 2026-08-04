<?php

declare(strict_types=1);

namespace App\Module\Admin\Application\Quote\Service;

use App\Infrastructure\Persistence\DoctrineUnitOfWork;
use App\Module\Admin\Application\Quote\DTO\QuoteServiceFormData;
use App\Module\Quote\Domain\Entity\Service;
use App\Module\Quote\Domain\Exception\QuoteOperationException;

final readonly class UpdateQuoteServiceHandler
{
    public function __construct(
        private DoctrineUnitOfWork $persistence,
        private QuoteServiceFormApplier $formApplier,
    ) {
    }

    public function update(Service $service, QuoteServiceFormData $data): Service
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
