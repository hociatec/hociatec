<?php

declare(strict_types=1);

namespace App\Module\Quote\Infrastructure\Repository;

use App\Module\Quote\Domain\Entity\QuoteItem;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<QuoteItem>
 */
class QuoteItemRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, QuoteItem::class);
    }
}
