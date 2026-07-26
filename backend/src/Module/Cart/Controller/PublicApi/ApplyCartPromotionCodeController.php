<?php

declare(strict_types=1);

namespace App\Module\Cart\Controller\PublicApi;

use App\Module\Cart\DTO\ApplyCartVoucherInput;
use App\Module\Cart\Service\CartFormatter;
use App\Module\Cart\Service\CartVoucherService;
use App\Module\User\Entity\User;
use App\Shared\Http\ApiResponse;
use App\Shared\Http\RateLimited;
use App\Shared\Validation\DtoValidator;
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
        $payload = '' !== $request->getContent() ? \App\Shared\Http\JsonPayload::decode($request) : [];
        $input = ApplyCartVoucherInput::fromArray($payload);
        $this->validator->validate($input);

        $user = $this->getUser();

        try {
            $cart = $this->cartVouchers->apply(
                $this->extractToken($request, ['cartToken' => $input->cartToken]),
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

    /**
     * @param array<string, mixed>|null $payload
     */
    private function extractToken(Request $request, ?array $payload): ?string
    {
        $headerToken = $request->headers->get('X-Cart-Token');
        if (is_string($headerToken) && '' !== $headerToken) {
            return $headerToken;
        }

        $payloadToken = $payload['cartToken'] ?? null;

        return is_string($payloadToken) && '' !== $payloadToken ? $payloadToken : null;
    }
}
