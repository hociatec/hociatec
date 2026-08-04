<?php

declare(strict_types=1);

namespace App\Module\Admin\Application\Operations\Service;

use App\Module\Admin\Application\Operations\Projection\AdminOperationsFormatter;
use App\Module\Catalog\Application\Port\ProductCatalogRepository;
use App\Module\Catalog\Infrastructure\Repository\StockMovementRepository;
use App\Module\Order\Application\Port\RefundRequestRepositoryPort;
use App\Module\Order\Domain\Entity\RefundRequest;
use App\Module\Support\Application\Port\SupportRequestRepositoryPort;
use App\Module\Support\Domain\Entity\SupportRequest;

final readonly class OperationsOverviewProvider
{
    private const LOW_STOCK_THRESHOLD = 3;

    public function __construct(
        private SupportRequestRepositoryPort $supportRequests,
        private RefundRequestRepositoryPort $refunds,
        private ProductCatalogRepository $products,
        private StockMovementRepository $stockMovements,
        private AdminOperationsFormatter $formatter,
    ) {
    }

    /**
     * @return array<string, mixed>
     */
    public function provide(): array
    {
        return [
            'support' => [
                'openCount' => $this->supportRequests->count(['status' => [
                    SupportRequest::STATUS_NEW,
                    SupportRequest::STATUS_IN_PROGRESS,
                    SupportRequest::STATUS_WAITING_CUSTOMER,
                ]]),
                'items' => array_map($this->formatter->supportRequest(...), $this->supportRequests->findBy([], ['updatedAt' => 'DESC'], 8)),
            ],
            'refunds' => [
                'pendingCount' => $this->refunds->count(['status' => RefundRequest::STATUS_REQUESTED]),
                'items' => array_map($this->formatter->refund(...), $this->refunds->findBy([], ['updatedAt' => 'DESC'], 8)),
            ],
            'stock' => [
                'lowStockThreshold' => self::LOW_STOCK_THRESHOLD,
                'lowStockCount' => $this->products->countLowStock(self::LOW_STOCK_THRESHOLD),
                'lowStockItems' => array_map($this->formatter->lowStockProduct(...), $this->products->findLowStock(self::LOW_STOCK_THRESHOLD, 8)),
                'movements' => array_map($this->formatter->stockMovement(...), $this->stockMovements->findBy([], ['createdAt' => 'DESC'], 8)),
            ],
            'emails' => ['items' => array_slice($this->formatter->emailLogs(), 0, 8)],
            'actions' => [
                ['label' => 'Exporter les commandes', 'href' => '/api/admin/operations/exports/orders.csv'],
                ['label' => 'Exporter les clients', 'href' => '/api/admin/operations/exports/customers.csv'],
                ['label' => 'Exporter les produits', 'href' => '/api/admin/operations/exports/products.csv'],
                ['label' => 'Exporter les devis', 'href' => '/api/admin/operations/exports/quotes.csv'],
            ],
        ];
    }
}
