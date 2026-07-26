<?php

declare(strict_types=1);

namespace App\Module\Admin\Order\Controller;

use App\Module\Admin\Order\DTO\OrderEmailScenarioInput;
use App\Module\Order\Entity\Order;
use App\Module\Order\Repository\OrderRepository;
use App\Module\Order\Service\OrderEventLogger;
use App\Module\Order\Service\OrderFormatter;
use App\Module\Order\Service\OrderNotificationEmailService;
use App\Shared\Http\ApiResponse;
use App\Shared\Validation\DtoValidator;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route('/api/admin/orders/{orderId}/resend-email', name: 'api_admin_orders_resend_email', methods: ['POST'])]
#[IsGranted('ROLE_ADMIN')]
final class ResendOrderEmailController extends AbstractController
{
    public function __construct(
        private readonly OrderRepository $orders,
        private readonly OrderNotificationEmailService $notifications,
        private readonly OrderEventLogger $events,
        private readonly DtoValidator $validator,
    ) {
    }

    public function __invoke(int $orderId, Request $request): JsonResponse
    {
        $order = $this->orders->find($orderId);
        if (null === $order) {
            return ApiResponse::error('Commande introuvable.', Response::HTTP_NOT_FOUND);
        }

        $payload = \App\Shared\Http\JsonPayload::decode($request);
        $input = OrderEmailScenarioInput::fromArray($payload);
        $this->validator->validate($input);
        $scenario = $input->scenario;

        try {
            $sent = match ($scenario) {
                'order_created' => $this->notifications->resendOrderCreated($order),
                'invoice_issued' => $this->notifications->resendInvoiceIssued($order),
                'current_status' => $this->resendCurrentStatusEmail($order),
                default => throw new \InvalidArgumentException('Scénario email invalide.'),
            };
        } catch (\InvalidArgumentException $exception) {
            return ApiResponse::error($exception->getMessage(), Response::HTTP_UNPROCESSABLE_ENTITY);
        } catch (\Throwable) {
            return ApiResponse::internalError('Impossible de renvoyer l’email.');
        }

        if (!$sent) {
            return ApiResponse::error('Aucun email pertinent à renvoyer pour cette commande.', Response::HTTP_CONFLICT);
        }

        $actor = $this->getUser();
        $this->events->log(
            $order,
            $actor instanceof \App\Module\User\Entity\User ? $actor : null,
            'email_manual_resend',
            'Renvoi manuel d’email depuis l’admin: '.$scenario.'.',
        );

        return ApiResponse::success(['order' => OrderFormatter::formatOrder($order)]);
    }

    private function resendCurrentStatusEmail(Order $order): bool
    {
        return match ($order->getStatus()) {
            Order::STATUS_DELIVERED, Order::STATUS_CANCELLED => $this->notifications->resendStatusChanged($order, $order->getStatus(), $order->getStatus()),
            default => false,
        };
    }
}
