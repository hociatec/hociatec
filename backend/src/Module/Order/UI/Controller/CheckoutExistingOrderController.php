<?php

declare(strict_types=1);

namespace App\Module\Order\UI\Controller;

use App\Module\Order\Application\DTO\CheckoutInput;
use App\Module\Order\Application\Projection\OrderFormatter;
use App\Module\Order\Application\Workflow\ExistingOrderCheckoutService;
use App\Shared\Application\Exception\ApiProblemException;
use App\Shared\Infrastructure\Http\ApiProblemResponse;
use App\Shared\Infrastructure\Http\ApiResponse;
use App\Shared\Infrastructure\Http\AuthenticatedDomainUserTrait;
use App\Shared\Infrastructure\Http\JsonRequestInput;
use App\Shared\Infrastructure\Http\RateLimited;
use App\Shared\Infrastructure\Validation\DtoValidator;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route('/api/orders/{orderId}/checkout', name: 'api_orders_existing_checkout', methods: ['POST'])]
#[IsGranted('ROLE_USER')]
#[RateLimited('checkout')]
final class CheckoutExistingOrderController extends AbstractController
{
    use AuthenticatedDomainUserTrait;

    public function __construct(
        private readonly ExistingOrderCheckoutService $checkout,
        private readonly DtoValidator $dtoValidator,
        private readonly OrderFormatter $orderFormatter,
    ) {
    }

    public function __invoke(Request $request, int $orderId): JsonResponse
    {
        try {
            $input = JsonRequestInput::decode($request, CheckoutInput::class);
            $this->dtoValidator->validate($input);
            $result = $this->checkout->checkout($this->currentUser(), $orderId, $input->addressId, $input->clientPlatform);
        } catch (ApiProblemException $exception) {
            return ApiProblemResponse::fromThrowable($exception, 'Impossible de lancer le règlement.');
        } catch (\InvalidArgumentException $exception) {
            return ApiProblemResponse::fromThrowable($exception, 'Impossible de lancer le règlement.');
        } catch (\DomainException $exception) {
            return ApiProblemResponse::fromThrowable($exception, 'Impossible de lancer le règlement.');
        } catch (\RuntimeException $exception) {
            return ApiProblemResponse::fromThrowable($exception, 'Impossible de lancer le règlement.');
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
