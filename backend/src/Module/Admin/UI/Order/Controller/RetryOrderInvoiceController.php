<?php

declare(strict_types=1);

namespace App\Module\Admin\UI\Order\Controller;

use App\Infrastructure\Http\ApiResponse;
use App\Module\Order\Application\Service\OrderEventLogger;
use App\Module\Order\Application\Service\OrderFormatter;
use App\Module\Order\Application\Service\OrderInvoiceDocumentService;
use App\Module\Order\Infrastructure\Repository\OrderRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route('/api/admin/orders/{orderId}/retry-invoice', name: 'api_admin_orders_retry_invoice', methods: ['POST'])]
#[IsGranted('ROLE_ADMIN')]
final class RetryOrderInvoiceController extends AbstractController
{
    public function __construct(
        private readonly OrderRepository $orders,
        private readonly OrderInvoiceDocumentService $documents,
        private readonly OrderEventLogger $events,
    ) {
    }

    public function __invoke(int $orderId): JsonResponse
    {
        $order = $this->orders->find($orderId);
        if (null === $order) {
            return ApiResponse::error('Commande introuvable.', Response::HTTP_NOT_FOUND);
        }

        try {
            $this->documents->ensureGenerated($order);
            $actor = $this->getUser();
            $this->events->log(
                $order,
                $actor instanceof \App\Module\User\Domain\Entity\User ? $actor : null,
                'invoice_regenerated',
                'Facture regénérée depuis l’admin.',
            );
        } catch (\RuntimeException) {
            return ApiResponse::internalError('Impossible de regénérer la facture.');
        }

        return ApiResponse::success(['order' => OrderFormatter::formatOrder($order)]);
    }
}
