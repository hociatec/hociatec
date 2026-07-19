<?php

declare(strict_types=1);

namespace App\Module\Admin\Order\Controller;

use App\Module\Order\Entity\Order;
use App\Module\Order\Repository\OrderRepository;
use App\Module\Order\Service\InvoiceDownloadNameBuilder;
use App\Module\Order\Service\OrderInvoiceDocumentService;
use App\Shared\Http\ApiResponse;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route('/api/admin/orders/{orderId}/invoice/pdf', name: 'api_admin_orders_invoice_pdf', methods: ['GET'])]
#[IsGranted('ROLE_ADMIN')]
final class DownloadOrderInvoicePdfController extends AbstractController
{
    public function __construct(
        private readonly OrderRepository $orders,
        private readonly OrderInvoiceDocumentService $documents,
        private readonly InvoiceDownloadNameBuilder $nameBuilder,
    ) {
    }

    public function __invoke(int $orderId): Response
    {
        $order = $this->orders->find($orderId);
        if ($order === null) {
            return ApiResponse::error('Commande introuvable.', Response::HTTP_NOT_FOUND);
        }

        if (in_array($order->getStatus(), [Order::STATUS_PENDING, Order::STATUS_CANCELLED], true)) {
            return ApiResponse::error('La facture est disponible uniquement pour une commande réglée non annulée.', Response::HTTP_BAD_REQUEST);
        }

        try {
            $pdf = $this->documents->getPdf($order);
        } catch (\Throwable $e) {
            return ApiResponse::error('Génération de facture PDF indisponible.', Response::HTTP_NOT_IMPLEMENTED, [$e->getMessage()]);
        }

        $filename = sprintf('%s.pdf', $this->nameBuilder->build($order));
        $response = new Response($pdf);
        $response->headers->set('Content-Type', 'application/pdf');
        $response->headers->set('Content-Disposition', 'attachment; filename="' . $filename . '"');

        return $response;
    }
}
