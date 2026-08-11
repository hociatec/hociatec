<?php

declare(strict_types=1);

namespace App\Module\Order\UI\Controller;

use App\Module\Order\Application\Factory\InvoiceDownloadNameBuilder;
use App\Module\Order\Application\Port\OrderRepositoryPort;
use App\Module\Order\Application\Workflow\OrderInvoiceDocumentService;
use App\Module\Order\Domain\Entity\Order;
use App\Module\Order\Domain\Security\OrderAccessPolicy;
use App\Module\User\Domain\Entity\User;
use App\Shared\Infrastructure\Http\ApiResponse;
use App\Shared\Infrastructure\Http\AttachmentResponseFactory;
use App\Shared\Infrastructure\Http\RateLimitKeyFactory;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\DependencyInjection\Attribute\Autowire;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\RateLimiter\RateLimiterFactory;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route('/api/orders/{orderId}/invoice/xml', name: 'api_orders_invoice_xml', methods: ['GET'])]
#[IsGranted('ROLE_USER')]
final class DownloadMyOrderInvoiceXmlController extends AbstractController
{
    public function __construct(
        private readonly OrderRepositoryPort $orders,
        private readonly OrderInvoiceDocumentService $documents,
        private readonly InvoiceDownloadNameBuilder $nameBuilder,
        private readonly OrderAccessPolicy $accessPolicy,
        private readonly AttachmentResponseFactory $attachments,
        private readonly RateLimitKeyFactory $rateLimitKeys,
        #[Autowire(service: 'limiter.private_file_download')]
        private readonly RateLimiterFactory $limiter,
    ) {
    }

    public function __invoke(int $orderId, Request $request): Response
    {
        $order = $this->orders->find($orderId);
        if (null === $order) {
            return ApiResponse::error('Commande introuvable.', Response::HTTP_NOT_FOUND);
        }

        /** @var User $user */
        $user = \App\Module\Auth\Infrastructure\Security\SymfonySecurityUser::domainUser($this->getUser());
        if (!$this->accessPolicy->canDownloadInvoice($user, $order)) {
            return ApiResponse::error('Commande introuvable.', Response::HTTP_NOT_FOUND);
        }
        $limit = $this->limiter->create($this->rateLimitKeys->forRequest($request, $user->getEmail().':invoice-xml'))->consume(1);
        if (!$limit->isAccepted()) {
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
