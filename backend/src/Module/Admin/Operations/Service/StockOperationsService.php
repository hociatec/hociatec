<?php

declare(strict_types=1);

namespace App\Module\Admin\Operations\Service;

use App\Module\Admin\Operations\Exception\OperationsResourceNotFoundException;
use App\Module\Catalog\Entity\Product;
use App\Module\Catalog\Entity\StockMovement;
use App\Module\Catalog\Repository\ProductRepository;
use App\Module\Catalog\Repository\StockMovementRepository;
use App\Module\User\Entity\User;
use Doctrine\ORM\EntityManagerInterface;

final readonly class StockOperationsService
{
    public function __construct(
        private ProductRepository $products,
        private StockMovementRepository $movements,
        private EntityManagerInterface $entityManager,
        private AdminOperationsFormatter $formatter,
    ) {
    }

    /**
     * @return list<array<string, mixed>>
     */
    public function list(): array
    {
        return array_map(
            $this->formatter->stockMovement(...),
            $this->movements->findBy([], ['createdAt' => 'DESC'], 100),
        );
    }

    /**
     * @return array<string, mixed>
     */
    public function create(int $productId, int $delta, string $reason, ?string $note, ?User $actor): array
    {
        $product = $this->products->find($productId);
        if (!$product instanceof Product) {
            throw new OperationsResourceNotFoundException('Produit introuvable.');
        }
        if (0 === $delta) {
            throw new \InvalidArgumentException('Le mouvement de stock doit être différent de zéro.');
        }

        $before = $product->getStock();
        $after = max(0, $before + $delta);
        $product->setStock($after);

        $movement = new StockMovement($product, $after - $before, $before, $after, $reason, $actor);
        $movement->setNote($note);
        $this->entityManager->persist($movement);
        $this->entityManager->flush();

        return $this->formatter->stockMovement($movement);
    }

    /**
     * @return array<string, mixed>
     */
    public function updateThreshold(int $productId, int $threshold): array
    {
        $product = $this->products->find($productId);
        if (!$product instanceof Product) {
            throw new OperationsResourceNotFoundException('Produit introuvable.');
        }
        if ($threshold < 0) {
            throw new \InvalidArgumentException('Le seuil doit être un entier positif.');
        }

        $product->setLowStockThreshold($threshold);
        $this->entityManager->flush();

        return $this->formatter->lowStockProduct($product);
    }
}
