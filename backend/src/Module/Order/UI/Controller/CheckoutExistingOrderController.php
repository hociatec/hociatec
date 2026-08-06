<?php

declare(strict_types=1);

namespace App\Module\Order\UI\Controller;

use App\Module\Order\Application\DTO\CheckoutInput;
use App\Module\Order\Application\Exception\CartCheckoutNotFoundException;
use App\Module\Order\Application\Projection\OrderFormatter;
use App\Module\Order\Application\Workflow\ExistingOrderCheckoutService;
use App\Module\User\Domain\Entity\User;
use App\Shared\Infrastructure\Http\ApiResponse;
use App\Shared\Application\Exception\ApiValidationException;
use App\Shared\Infrastructure\Http\InvalidJsonPayloadException;
use App\Shared\Infrastructure\Http\JsonRequestInput;
use App\Shared\Infrastructure\Http\RateLimited;
use App\Shared\Infrastructure\Validation\DtoValidator;
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
            $result = $this->checkout->checkout($this->currentUser(), $orderId, $input->addressId);
        } catch (CartCheckoutNotFoundException $exception) {
            return ApiResponse::error($exception->getMessage(), Response::HTTP_NOT_FOUND);
        } catch (ApiValidationException $exception) {
            return ApiResponse::error($exception->getMessage(), $exception->statusCode, $exception->details);
        } catch (InvalidJsonPayloadException) {
            return ApiResponse::error('Payload de checkout invalide.', Response::HTTP_BAD_REQUEST);
        } catch (\InvalidArgumentException|\RuntimeException $exception) {
            return ApiResponse::error('Impossible de lancer le règlement.', Response::HTTP_BAD_REQUEST, [$exception->getMessage()]);
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

    private function currentUser(): User
    {
        $user = \App\Module\Auth\Infrastructure\Security\SymfonySecurityUser::domainUser($this->getUser());
        if (!$user instanceof User) {
            throw $this->createAccessDeniedException();
        }

        return $user;
    }
}
