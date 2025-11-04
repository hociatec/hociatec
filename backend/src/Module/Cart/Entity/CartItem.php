<?php

declare(strict_types=1);

namespace App\Module\Cart\Entity;

use App\Module\Cart\Repository\CartItemRepository;
use App\Module\Catalog\Entity\Product;
use Doctrine\ORM\Mapping as ORM;
use InvalidArgumentException;

#[ORM\Entity(repositoryClass: CartItemRepository::class)]
#[ORM\Table(name: 'cart_items')]
#[ORM\UniqueConstraint(name: 'cart_item_unique_product', columns: ['cart_id', 'product_id'])]
class CartItem
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\ManyToOne(inversedBy: 'items')]
    #[ORM\JoinColumn(nullable: false, onDelete: 'CASCADE')]
    private CartSession $cart;

    #[ORM\ManyToOne]
    #[ORM\JoinColumn(nullable: false, onDelete: 'CASCADE')]
    private Product $product;

    #[ORM\Column(type: 'integer')]
    private int $quantity;

    public function __construct(CartSession $cart, Product $product, int $quantity = 1)
    {
        $this->cart = $cart;
        $this->product = $product;
        $this->setQuantity($quantity);
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getCart(): CartSession
    {
        return $this->cart;
    }

    public function setCart(CartSession $cart): self
    {
        $this->cart = $cart;

        return $this;
    }

    public function getProduct(): Product
    {
        return $this->product;
    }

    public function getQuantity(): int
    {
        return $this->quantity;
    }

    public function setQuantity(int $quantity): self
    {
        if ($quantity < 1) {
            throw new InvalidArgumentException('La quantite doit etre superieure ou egale a 1.');
        }

        $this->quantity = $quantity;

        return $this;
    }

    public function increaseQuantity(int $amount = 1): self
    {
        if ($amount < 1) {
            throw new InvalidArgumentException('L\'augmentation doit etre superieure ou egale a 1.');
        }

        $this->quantity += $amount;

        return $this;
    }

    public function replaceProduct(Product $product): self
    {
        $this->product = $product;

        return $this;
    }
}
