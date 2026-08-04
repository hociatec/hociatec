<?php

declare(strict_types=1);

namespace App\Module\Quote\Application\Service;

use App\Module\Quote\Domain\Entity\Quote;
use App\Module\Quote\Domain\Entity\QuoteItem;
use Doctrine\ORM\EntityManagerInterface;

final readonly class QuotePersistence
{
    public function __construct(private EntityManagerInterface $entityManager)
    {
    }

    public function save(Quote $quote): void
    {
        $this->entityManager->persist($quote);
    }

    public function addItem(Quote $quote, QuoteItem $item): void
    {
        $quote->addItem($item);
        $this->entityManager->persist($item);
    }

    public function removeItem(QuoteItem $item): void
    {
        $this->entityManager->remove($item);
    }

    public function flush(): void
    {
        $this->entityManager->flush();
    }

    public function delete(Quote $quote): void
    {
        $this->entityManager->remove($quote);
    }
}
