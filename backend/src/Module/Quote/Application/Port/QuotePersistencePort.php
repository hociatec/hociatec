<?php

declare(strict_types=1);

namespace App\Module\Quote\Application\Port;

use App\Module\Quote\Domain\Entity\Quote;
use App\Module\Quote\Domain\Entity\QuoteItem;

interface QuotePersistencePort
{
    public function save(Quote $quote): void;

    public function addItem(Quote $quote, QuoteItem $item): void;

    public function removeItem(QuoteItem $item): void;

    public function commit(): void;

    public function delete(Quote $quote): void;
}
