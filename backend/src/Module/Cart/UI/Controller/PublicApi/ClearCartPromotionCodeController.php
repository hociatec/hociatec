<?php

declare(strict_types=1);

namespace App\Module\Cart\UI\Controller\PublicApi;

use App\Module\Cart\Application\Projection\CartFormatter;
use App\Module\Cart\Application\Workflow\CartVoucherService;
use App\Module\User\Domain\Entity\User;
use App\Shared\Infrastructure\Http\ApiResponse;
use App\Shared\Infrastructure\Http\RateLimited;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/api/public/cart/voucher-code', name: 'api_public_cart_clear_voucher_code', methods: ['DELETE'])]
#[RateLimited('public_api')]
final class ClearCartPromotionCodeController extends AbstractController
{
    public function __construct(
        private readonly CartVoucherService $cartVouchers,
        private readonly CartFormatter $cartFormatter,
    ) {
    }

    public function __invoke(Request $request): JsonResponse
    {
        $user = \App\Module\Auth\Infrastructure\Security\SymfonySecurityUser::domainUser($this->getUser());
        $cart = $this->cartVouchers->clear(
            $this->extractToken($request),
            $user instanceof User ? $user : null,
        );

        $response = ApiResponse::success([
            'cart' => $this->cartFormatter->formatCart($cart, $user instanceof User ? $user : null),
        ]);
        $response->headers->set('X-Cart-Token', $cart->getToken());

        return $response;
    }

    private function extractToken(Request $request): ?string
    {
        $headerToken = $request->headers->get('X-Cart-Token');
        if (is_string($headerToken) && '' !== $headerToken) {
            return $headerToken;
        }

        return null;
    }
}
