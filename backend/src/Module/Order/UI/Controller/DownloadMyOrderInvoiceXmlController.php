<?php

declare(strict_types=1);

namespace App\Module\Order\UI\Controller;

use App\Module\Order\Application\Factory\InvoiceDownloadNameBuilder;
use App\Module\Order\Application\Workflow\OrderInvoiceDocumentService;
use App\Module\Order\Domain\Entity\Order;
use App\Module\Order\UI\Http\MyOrderInvoiceAccessService;
use App\Module\Order\UI\Http\PrivateFileDownloadRateLimiter;
use App\Shared\Infrastructure\Http\ApiResponse;
use App\Shared\Infrastructure\Http\AttachmentResponseFactory;
use App\Shared\Infrastructure\Http\AuthenticatedDomainUserTrait;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route('/api/orders/{orderId}/invoice/xml', name: 'api_orders_invoice_xml', methods: ['GET'])]
#[IsGranted('ROLE_USER')]
final class DownloadMyOrderInvoiceXmlController extends AbstractController
{
    use AuthenticatedDomainUserTrait;

    public function __construct(
        private readonly MyOrderInvoiceAccessService $invoiceAccess,
        private readonly OrderInvoiceDocumentService $documents,
        private readonly InvoiceDownloadNameBuilder $nameBuilder,
        private readonly AttachmentResponseFactory $attachments,
        private readonly PrivateFileDownloadRateLimiter $rateLimiter,
    ) {
    }

    public function __invoke(int $orderId, Request $request): Response
    {
        $user = $this->currentUser();
        $order = $this->invoiceAccess->findAccessibleOrder($user, $orderId);
        if (null === $order) {
            return ApiResponse::error('Commande introuvable.', Response::HTTP_NOT_FOUND);
        }

        if (!$this->rateLimiter->isAccepted($request, $user->getEmail().':invoice-xml')) {
            return ApiResponse::error('Trop de téléchargements privés. Veuillez réessayer plus tard.', Response::HTTP_TOO_MANY_REQUESTS);
        }

        if (in_array($order->getStatus(), [Order::STATUS_PENDING, Order::STATUS_CANCELLED], true)) {
            return ApiResponse::error('La facture est disponible uniquement pour une commande réglée non annulée.', Response::HTTP_BAD_REQUEST);
        }

        try {
            $xml = $this->documents->getXml($order);
        } catch (\RuntimeException) {
            return ApiResponse::error('Génération de facture électronique indisponible.', Response::HTTP_NOT_IMPLEMENTED);
        }

        return $this->attachments->create($xml, sprintf('%s.xml', $this->nameBuilder->build($order)), 'application/xml; charset=UTF-8');
    }
}
