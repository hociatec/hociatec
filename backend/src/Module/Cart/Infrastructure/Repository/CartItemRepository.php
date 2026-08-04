<?php

declare(strict_types=1);

namespace App\Module\Cart\Infrastructure\Repository;

use App\Module\Cart\Domain\Entity\CartItem;
use App\Module\Cart\Domain\Entity\CartSession;
use App\Module\Catalog\Domain\Entity\Product;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<CartItem>
 */
final class CartItemRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, CartItem::class);
    }

    public function findOneByCartAndProduct(CartSession $cart, Product $product): ?CartItem
    {
        return $this->createQueryBuilder('item')
            ->andWhere('item.cart = :cart')
            ->andWhere('item.product = :product')
            ->setParameter('cart', $cart)
            ->setParameter('product', $product)
            ->getQuery()
            ->getOneOrNullResult();
    }
}
