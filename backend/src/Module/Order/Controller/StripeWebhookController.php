<?php

declare(strict_types=1);

namespace App\Module\Order\Controller;

use App\Module\Order\Service\StripeWebhookService;
use App\Shared\Http\ApiResponse;
use Psr\Log\LoggerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/api/stripe/webhook', name: 'api_stripe_webhook', methods: ['POST'])]
final class StripeWebhookController extends AbstractController
{
    public function __construct(
        private readonly StripeWebhookService $webhooks,
        private readonly LoggerInterface $logger,
    ) {
    }

    public function __invoke(Request $request): JsonResponse
    {
        try {
            $result = $this->webhooks->handle($request->getContent(), $request->headers->get('Stripe-Signature'));
        } catch (\InvalidArgumentException $exception) {
            $this->logger->warning('Stripe webhook rejected.', [
                'error' => $exception->getMessage(),
            ]);

            return ApiResponse::error('Webhook Stripe invalide.', Response::HTTP_BAD_REQUEST);
        }

        return ApiResponse::success(['received' => true] + $result);
    }
}
