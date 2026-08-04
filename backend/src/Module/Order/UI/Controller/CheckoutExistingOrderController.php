<?php

declare(strict_types=1);

namespace App\Module\Order\UI\Controller;

use App\Infrastructure\Http\ApiResponse;
use App\Infrastructure\Http\ApiValidationException;
use App\Infrastructure\Http\InvalidJsonPayloadException;
use App\Infrastructure\Http\RateLimited;
use App\Infrastructure\Validation\DtoValidator;
use App\Module\Order\Application\DTO\CheckoutInput;
use App\Module\Order\Application\Projection\OrderFormatter;
use App\Module\Order\Application\Service\StripeCheckoutService;
use App\Module\Order\Domain\Entity\Order;
use App\Module\Order\Domain\Security\OrderAccessPolicy;
use App\Module\Order\Infrastructure\Repository\OrderRepository;
use App\Module\User\Domain\Entity\User;
use App\Module\User\Infrastructure\Repository\ShippingAddressRepository;
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
        private readonly OrderAccessPolicy $accessPolicy,
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
        if (!$this->accessPolicy->canCheckout($user, $order)) {
            return ApiResponse::error('Commande introuvable.', Response::HTTP_NOT_FOUND);
        }

        if (Order::STATUS_CONFIRMED === $order->getStatus() || Order::STATUS_DELIVERED === $order->getStatus()) {
            return ApiResponse::successItem('order', OrderFormatter::formatOrder($order));
        }

        if (Order::STATUS_PENDING !== $order->getStatus()) {
            return ApiResponse::error('Cette commande ne peut pas être réglée.', Response::HTTP_BAD_REQUEST);
        }

        if ($order->getTotalPriceCents() <= 0 || $order->getItems()->isEmpty()) {
            return ApiResponse::error('Cette commande ne contient rien à régler.', Response::HTTP_BAD_REQUEST);
        }

        try {
            $input = \App\Infrastructure\Http\JsonRequestInput::decode($request, CheckoutInput::class);
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
