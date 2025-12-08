<?php

declare(strict_types=1);

namespace App\Module\Comment\Repository;

use App\Module\Comment\Entity\ProductComment;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<ProductComment>
 */
class ProductCommentRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, ProductComment::class);
    }
}
