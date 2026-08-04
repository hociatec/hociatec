<?php

declare(strict_types=1);

namespace App\Module\Loyalty\UI\Controller;

use App\Infrastructure\Http\ApiResponse;
use App\Infrastructure\Http\InvalidJsonPayloadException;
use App\Module\Loyalty\Application\Service\LoyaltyService;
use App\Module\Loyalty\Domain\Exception\LoyaltyOperationException;
use App\Module\User\Domain\Entity\User;
use App\Module\Voucher\Application\Service\VoucherFormatter;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route('/api/loyalty/me')]
#[IsGranted('ROLE_USER')]
final class MyLoyaltyController extends AbstractController
{
    public function __construct(private readonly LoyaltyService $loyalty)
    {
    }

    #[Route('', name: 'api_loyalty_me', methods: ['GET'])]
    public function show(): JsonResponse
    {
        /** @var User $user */
        $user = $this->getUser();

        return ApiResponse::success(['loyalty' => $this->formatLoyalty($user)]);
    }

    #[Route('/convert', name: 'api_loyalty_me_convert', methods: ['POST'])]
    public function convert(Request $request): JsonResponse
    {
        /** @var User $user */
        $user = $this->getUser();

        try {
            $payload = \App\Infrastructure\Http\JsonPayload::decode($request);
            $points = (int) ($payload['points'] ?? 0);
            $voucher = $this->loyalty->convertPointsToVoucher($user, $points);
        } catch (\InvalidArgumentException $exception) {
            return ApiResponse::error($exception->getMessage(), Response::HTTP_BAD_REQUEST);
        } catch (InvalidJsonPayloadException) {
            return ApiResponse::error('Payload invalide.', Response::HTTP_BAD_REQUEST);
        } catch (LoyaltyOperationException) {
            return ApiResponse::error('Impossible de convertir ce solde.', Response::HTTP_BAD_REQUEST);
        }

        return ApiResponse::created([
            'loyalty' => $this->formatLoyalty($user),
            'voucher' => VoucherFormatter::formatVoucher($voucher),
        ]);
    }

    /** @return array<string, mixed> */
    private function formatLoyalty(User $user): array
    {
        $points = $user->getLoyaltyPointsBalance();

        return [
            'points' => $points,
            'euroCents' => $this->loyalty->pointsToCents($points),
            'pointsPerEuroEarned' => LoyaltyService::EARNING_POINTS_PER_EURO,
            'pointsPerEuroConverted' => LoyaltyService::POINTS_PER_EURO,
        ];
    }
}
