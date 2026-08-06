<?php

declare(strict_types=1);

namespace App\Module\Admin\Application\Operations\Converter;

use App\Module\Quote\Domain\Entity\Quote;

final readonly class QuoteConversionPolicy
{
    public function assertConvertible(Quote $quote): void
    {
        if (null !== $quote->getConvertedOrderId()) {
            throw new \InvalidArgumentException(sprintf('Ce devis a déjà été converti en commande %s.', (string) $quote->getConvertedOrderNumber()));
        }
        if (Quote::STATUS_ACCEPTED !== $quote->getStatus()) {
            throw new \InvalidArgumentException('Le devis doit être accepté avant conversion en commande.');
        }
        if ($quote->getItems()->isEmpty()) {
            throw new \InvalidArgumentException('Le devis ne contient aucune ligne à convertir.');
        }
    }
}
