<?php

declare(strict_types=1);

namespace App\Module\Order\UI\Controller;

use App\Module\Order\Application\DTO\CheckoutInput;
use App\Module\Order\Application\Exception\CartCheckoutConflictException;
use App\Module\Order\Application\Exception\CartCheckoutNotFoundException;
use App\Module\Order\Application\Projection\OrderFormatter;
use App\Module\Order\Application\Workflow\CartCheckoutService;
use App\Shared\Application\Exception\ApiValidationException;
use App\Shared\Infrastructure\Http\ApiResponse;
use App\Shared\Infrastructure\Http\AuthenticatedDomainUserTrait;
use App\Shared\Infrastructure\Http\RateLimited;
use App\Shared\Infrastructure\Http\RequestHeaderValueResolver;
use App\Shared\Infrastructure\Validation\DtoValidator;
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
    use AuthenticatedDomainUserTrait;

    public function __construct(
        private readonly CartCheckoutService $checkout,
        private readonly DtoValidator $dtoValidator,
        private readonly OrderFormatter $orderFormatter,
        private readonly RequestHeaderValueResolver $headers,
    ) {
    }

    public function __invoke(Request $request): JsonResponse
    {
        $token = $this->headers->nonEmptyString($request, 'X-Cart-Token');
        if (null === $token) {
            return ApiResponse::error('Token de panier manquant.', Response::HTTP_BAD_REQUEST);
        }

        try {
            $payload = \App\Shared\Infrastructure\Http\JsonRequestInput::payload($request);
            $input = CheckoutInput::fromArray($payload);
            $this->dtoValidator->validate($input);
            $result = $this->checkout->checkout(
                $this->currentUser(),
                $token,
                $input->addressId,
            );
        } catch (CartCheckoutNotFoundException $exception) {
            return ApiResponse::error($exception->getMessage(), Response::HTTP_NOT_FOUND);
        } catch (CartCheckoutConflictException $exception) {
            return ApiResponse::error($exception->getMessage(), Response::HTTP_CONFLICT);
        } catch (ApiValidationException $exception) {
            return ApiResponse::error($exception->getMessage(), $exception->statusCode, $exception->details);
        } catch (\InvalidArgumentException $exception) {
            return ApiResponse::error('Impossible de valider la commande.', Response::HTTP_BAD_REQUEST, [$exception->getMessage()]);
        }

        if (null !== $result->order) {
            return ApiResponse::successItem('order', $this->orderFormatter->formatOrder($result->order));
        }

        return ApiResponse::created([
            'mode' => 'redirect',
            'checkoutUrl' => $result->checkout?->getCheckoutUrl(),
            'checkoutSessionId' => $result->checkout?->getStripeSessionId(),
        ]);
    }
}
