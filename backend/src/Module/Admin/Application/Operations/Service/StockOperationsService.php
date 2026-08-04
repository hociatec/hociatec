<?php

declare(strict_types=1);

namespace App\Module\Admin\Application\Operations\Service;

use App\Module\Admin\Application\Operations\Exception\OperationsResourceNotFoundException;
use App\Module\Catalog\Domain\Entity\Product;
use App\Module\Catalog\Domain\Entity\StockMovement;
use App\Module\Catalog\Infrastructure\Repository\ProductRepository;
use App\Module\Catalog\Infrastructure\Repository\StockMovementRepository;
use App\Module\User\Domain\Entity\User;

final readonly class StockOperationsService
{
    public function __construct(
        private ProductRepository $products,
        private StockMovementRepository $movements,
        private OperationsPersistence $persistence,
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
        if (0 === $delta) {
            throw new \InvalidArgumentException('Le mouvement de stock doit être différent de zéro.');
        }

        return $this->persistence->transactional(function () use ($productId, $delta, $reason, $note, $actor): array {
            $product = $this->products->findForUpdate($productId);
            if (!$product instanceof Product) {
                throw new OperationsResourceNotFoundException('Produit introuvable.');
            }

            $before = $product->getStock();
            $after = max(0, $before + $delta);
            $product->setStock($after);

            $movement = new StockMovement($product, $after - $before, $before, $after, $reason, $actor);
            $movement->setNote($note);
            $this->persistence->persist($movement);
            $this->persistence->flush();

            return $this->formatter->stockMovement($movement);
        });
    }

    /**
     * @return array<string, mixed>
     */
    public function updateThreshold(int $productId, int $threshold): array
    {
        if ($threshold < 0) {
            throw new \InvalidArgumentException('Le seuil doit être un entier positif.');
        }

        return $this->persistence->transactional(function () use ($productId, $threshold): array {
            $product = $this->products->findForUpdate($productId);
            if (!$product instanceof Product) {
                throw new OperationsResourceNotFoundException('Produit introuvable.');
            }

            $product->setLowStockThreshold($threshold);
            $this->persistence->flush();

            return $this->formatter->lowStockProduct($product);
        });
    }
}
