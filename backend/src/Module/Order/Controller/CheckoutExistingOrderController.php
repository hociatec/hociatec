<?php

declare(strict_types=1);

namespace App\Module\Order\Controller;

use App\Module\Order\DTO\CheckoutInput;
use App\Module\Order\Entity\Order;
use App\Module\Order\Repository\OrderRepository;
use App\Module\Order\Service\OrderFormatter;
use App\Module\Order\Service\StripeCheckoutService;
use App\Module\User\Entity\User;
use App\Module\User\Repository\ShippingAddressRepository;
use App\Shared\Http\ApiResponse;
use App\Shared\Http\ApiValidationException;
use App\Shared\Http\InvalidJsonPayloadException;
use App\Shared\Http\JsonPayload;
use App\Shared\Http\RateLimited;
use App\Shared\Validation\DtoValidator;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route('/api/orders/{orderId}/checkout', name: 'api_orders_existing_checkout', methods: ['POST'])]
#[IsGranted('ROLE_USER')]
#[RateLimited('checkout')]
final class CheckoutExistingOrderController extends AbstractController
{
    public function __construct(
        private readonly OrderRepository $orders,
        private readonly ShippingAddressRepository $addresses,
        private readonly StripeCheckoutService $stripeCheckout,
        private readonly DtoValidator $dtoValidator,
    ) {
    }

    public function __invoke(Request $request, int $orderId): JsonResponse
    {
        $order = $this->orders->find($orderId);
        if (!$order instanceof Order) {
            return ApiResponse::error('Commande introuvable.', Response::HTTP_NOT_FOUND);
        }

        /** @var User $user */
        $user = $this->getUser();
        if ($order->getUser()->getId() !== $user->getId()) {
            return ApiResponse::error('Commande introuvable.', Response::HTTP_NOT_FOUND);
        }

        if (Order::STATUS_CONFIRMED === $order->getStatus() || Order::STATUS_DELIVERED === $order->getStatus()) {
            return ApiResponse::success(['order' => OrderFormatter::formatOrder($order)]);
        }

        if (Order::STATUS_PENDING !== $order->getStatus()) {
            return ApiResponse::error('Cette commande ne peut pas être réglée.', Response::HTTP_BAD_REQUEST);
        }

        if ($order->getTotalPriceCents() <= 0 || $order->getItems()->isEmpty()) {
            return ApiResponse::error('Cette commande ne contient rien à régler.', Response::HTTP_BAD_REQUEST);
        }

        try {
            $input = CheckoutInput::fromArray(JsonPayload::decode($request));
            $this->dtoValidator->validate($input);
        } catch (ApiValidationException $exception) {
            return ApiResponse::error($exception->getMessage(), $exception->statusCode, $exception->details);
        } catch (InvalidJsonPayloadException) {
            return ApiResponse::error('Payload de checkout invalide.', Response::HTTP_BAD_REQUEST);
        }

        $addressId = $input->addressId ?? 0;
        $shipping = $addressId > 0
            ? $this->addresses->findOneForUser($addressId, $user)
            : $this->addresses->findFirstForUser($user);

        if (null === $shipping) {
            return ApiResponse::error('Aucune adresse de livraison trouvée.', Response::HTTP_BAD_REQUEST);
        }

        try {
            $checkout = $this->stripeCheckout->createHostedCheckoutForOrder($user, $order, $shipping);
        } catch (\InvalidArgumentException $exception) {
            return ApiResponse::error('Impossible de lancer le règlement.', Response::HTTP_BAD_REQUEST, [$exception->getMessage()]);
        } catch (\RuntimeException $exception) {
            return ApiResponse::error('Impossible de lancer le règlement.', Response::HTTP_BAD_REQUEST, [$exception->getMessage()]);
        }

        return ApiResponse::created([
            'mode' => 'redirect',
            'checkoutUrl' => $checkout->getCheckoutUrl(),
            'checkoutSessionId' => $checkout->getStripeSessionId(),
        ]);
    }
}
