<?php

declare(strict_types=1);

namespace App\Module\Admin\Application\Quote\Service;

use App\Module\Admin\Application\Quote\DTO\QuoteServiceFormData;
use App\Module\Quote\Domain\Entity\Service;
use App\Module\Quote\Domain\Exception\QuoteOperationException;
use App\Shared\Infrastructure\Doctrine\DoctrineUnitOfWork;

final readonly class CreateQuoteServiceHandler
{
    public function __construct(
        private DoctrineUnitOfWork $persistence,
        private QuoteServiceFormApplier $formApplier,
    ) {
    }

    public function create(QuoteServiceFormData $data): Service
    {
        $this->formApplier->validate($data, true);
        $service = new Service($data->title, $data->priceCents ?? 0, $data->vatRateBps ?? 0);
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
