<?php

declare(strict_types=1);

namespace App\Module\Admin\UI\Order\Controller;

use App\Module\Order\Application\Factory\InvoiceDownloadNameBuilder;
use App\Module\Order\Application\Workflow\OrderInvoiceDocumentService;
use App\Module\Order\Domain\Entity\Order;
use App\Module\Order\Application\Port\OrderRepositoryPort;
use App\Shared\Infrastructure\Http\ApiResponse;
use App\Shared\Infrastructure\Http\AttachmentResponseFactory;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route('/api/admin/orders/{orderId}/invoice/pdf', name: 'api_admin_orders_invoice_pdf', methods: ['GET'])]
#[IsGranted('ROLE_ADMIN')]
final class DownloadOrderInvoicePdfController extends AbstractController
{
    public function __construct(
        private readonly OrderRepositoryPort $orders,
        private readonly OrderInvoiceDocumentService $documents,
        private readonly InvoiceDownloadNameBuilder $nameBuilder,
        private readonly AttachmentResponseFactory $attachments,
    ) {
    }

    public function __invoke(int $orderId): Response
    {
        $order = $this->orders->find($orderId);
        if (null === $order) {
            return ApiResponse::error('Commande introuvable.', Response::HTTP_NOT_FOUND);
        }

        if (in_array($order->getStatus(), [Order::STATUS_PENDING, Order::STATUS_CANCELLED], true)) {
            return ApiResponse::error('La facture est disponible uniquement pour une commande réglée non annulée.', Response::HTTP_BAD_REQUEST);
        }

        try {
            $pdf = $this->documents->getPdf($order);
        } catch (\RuntimeException) {
            return ApiResponse::error('Génération de facture PDF indisponible.', Response::HTTP_NOT_IMPLEMENTED);
        }

        return $this->attachments->create($pdf, sprintf('%s.pdf', $this->nameBuilder->build($order)), 'application/pdf');
    }
}
