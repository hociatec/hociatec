<?php

declare(strict_types=1);

namespace App\Module\Admin\Payment\Controller;

use App\Module\Admin\Payment\Service\AdminPaymentFormatter;
use App\Module\Admin\Payment\Service\StripePaymentDetailsProvider;
use App\Module\Order\Entity\OrderCheckoutSession;
use App\Module\Order\Repository\OrderCheckoutSessionRepository;
use App\Module\Order\Service\StripeCheckoutSessionSyncService;
use App\Shared\Http\ApiResponse;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route('/api/admin/payments/{paymentId}', name: 'api_admin_payments_show', methods: ['GET'])]
#[IsGranted('ROLE_ADMIN')]
final class ShowPaymentController extends AbstractController
{
    public function __construct(
        private readonly OrderCheckoutSessionRepository $payments,
        private readonly StripeCheckoutSessionSyncService $stripeSync,
        private readonly AdminPaymentFormatter $formatter,
        private readonly StripePaymentDetailsProvider $stripeDetails,
    ) {
    }

    public function __invoke(int $paymentId): JsonResponse
    {
        $payment = $this->payments->find($paymentId);
        if (!$payment instanceof OrderCheckoutSession) {
            return ApiResponse::error('Paiement introuvable.', Response::HTTP_NOT_FOUND);
        }

        $this->stripeSync->syncPayment($payment);

        return ApiResponse::success([
            'payment' => $this->formatter->detail($payment),
            'liveStripe' => $this->stripeDetails->provide($payment),
        ]);
    }
}
