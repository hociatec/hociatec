<?php

declare(strict_types=1);

namespace App\Module\Order\Controller;

use App\Module\Order\Exception\CartCheckoutConflictException;
use App\Module\Order\Exception\CartCheckoutNotFoundException;
use App\Module\Order\Service\CartCheckoutService;
use App\Module\Order\Service\OrderFormatter;
use App\Module\User\Entity\User;
use App\Shared\Http\ApiResponse;
use App\Shared\Http\RateLimited;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route('/api/orders/checkout', name: 'api_orders_checkout', methods: ['POST'])]
#[IsGranted('ROLE_USER')]
#[RateLimited('checkout')]
final class CheckoutController extends AbstractController
{
    public function __construct(private readonly CartCheckoutService $checkout)
    {
    }

    public function __invoke(Request $request): JsonResponse
    {
        $token = $this->cartToken($request);
        if (null === $token) {
            return ApiResponse::error('Token de panier manquant.', Response::HTTP_BAD_REQUEST);
        }

        try {
            $payload = '' !== $request->getContent() ? $request->toArray() : [];
            $result = $this->checkout->checkout(
                $this->currentUser(),
                $token,
                isset($payload['addressId']) ? (int) $payload['addressId'] : null,
            );
        } catch (CartCheckoutNotFoundException $exception) {
            return ApiResponse::error($exception->getMessage(), Response::HTTP_NOT_FOUND);
        } catch (CartCheckoutConflictException $exception) {
            return ApiResponse::error($exception->getMessage(), Response::HTTP_CONFLICT);
        } catch (\InvalidArgumentException $exception) {
            return ApiResponse::error('Impossible de valider la commande.', Response::HTTP_BAD_REQUEST, [$exception->getMessage()]);
        } catch (\Throwable $exception) {
            return ApiResponse::error('Impossible de valider la commande.', Response::HTTP_BAD_REQUEST, [$exception->getMessage()]);
        }

        if (null !== $result->order) {
            return ApiResponse::success(['order' => OrderFormatter::formatOrder($result->order)]);
        }

        return ApiResponse::created([
            'mode' => 'redirect',
            'checkoutUrl' => $result->checkout?->getCheckoutUrl(),
            'checkoutSessionId' => $result->checkout?->getStripeSessionId(),
        ]);
    }

    private function cartToken(Request $request): ?string
    {
        $token = $request->headers->get('X-Cart-Token') ?? $request->query->get('cartToken');

        return is_string($token) && '' !== $token ? $token : null;
    }

    private function currentUser(): User
    {
        $user = $this->getUser();
        if (!$user instanceof User) {
            throw $this->createAccessDeniedException();
        }

        return $user;
    }
}
