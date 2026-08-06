<?php

declare(strict_types=1);

namespace App\Module\Cart\Application\Workflow;

use App\Module\Cart\Application\Provider\CartItemResolver;
use App\Module\Cart\Application\Provider\CartSessionProvider;
use App\Module\Catalog\Application\Port\ProductCatalogRepository;
use App\Shared\Application\UnitOfWork;

readonly class CartSessionWorkflow
{
    use CartAddProductTrait;
    use CartMutationTrait;
    use CartReaderTrait;

    public function __construct(
        private CartSessionProvider $cartSessions,
        private CartItemResolver $cartItems,
        private UnitOfWork $persistence,
        private ProductCatalogRepository $products,
    ) {
    }
}
