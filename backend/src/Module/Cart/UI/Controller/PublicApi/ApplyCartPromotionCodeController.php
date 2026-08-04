<?php

declare(strict_types=1);

namespace App\Module\Cart\UI\Controller\PublicApi;

use App\Infrastructure\Http\ApiResponse;
use App\Infrastructure\Http\RateLimited;
use App\Infrastructure\Validation\DtoValidator;
use App\Module\Cart\Application\DTO\ApplyCartVoucherInput;
use App\Module\Cart\Application\Projection\CartFormatter;
use App\Module\Cart\Application\Service\CartVoucherService;
use App\Module\User\Domain\Entity\User;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/api/public/cart/voucher-code', name: 'api_public_cart_apply_voucher_code', methods: ['POST'])]
#[RateLimited('public_api')]
final class ApplyCartPromotionCodeController extends AbstractController
{
    public function __construct(
        private readonly CartVoucherService $cartVouchers,
        private readonly CartFormatter $cartFormatter,
        private readonly DtoValidator $validator,
    ) {
    }

    public function __invoke(Request $request): JsonResponse
    {
        $payload = \App\Infrastructure\Http\JsonRequestInput::optionalPayload($request);
        $input = ApplyCartVoucherInput::fromArray($payload);
        $this->validator->validate($input);

        $user = $this->getUser();

        try {
            $cart = $this->cartVouchers->apply(
                $this->extractToken($request),
                $input->voucherCode,
                $user instanceof User ? $user : null,
            );
        } catch (\InvalidArgumentException $exception) {
            return ApiResponse::error($exception->getMessage(), JsonResponse::HTTP_BAD_REQUEST);
        }

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
