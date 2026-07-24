<?php

declare(strict_types=1);

namespace App\Module\Admin\Promotion\Controller;

use App\Module\Promotion\Entity\Promotion;
use App\Module\Promotion\Repository\PromotionRepository;
use App\Module\Promotion\Service\PromotionFormatter;
use App\Shared\Http\ApiResponse;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route('/api/admin/promotions/{promotionId}', name: 'api_admin_promotions_update', methods: ['PUT'], requirements: ['promotionId' => '\d+'])]
#[IsGranted('ROLE_ADMIN')]
final class UpdatePromotionController extends AbstractController
{
    public function __construct(
        private readonly PromotionRepository $promotions,
        private readonly EntityManagerInterface $entityManager,
    ) {
    }

    public function __invoke(int $promotionId, Request $request): JsonResponse
    {
        $promotion = $this->promotions->find($promotionId);
        if (null === $promotion) {
            return ApiResponse::error('Promotion introuvable.', Response::HTTP_NOT_FOUND);
        }

        try {
            $payload = $request->toArray();
        } catch (\Throwable) {
            return ApiResponse::error('Payload invalide.', Response::HTTP_BAD_REQUEST);
        }

        $name = trim((string) ($payload['name'] ?? ''));
        $slug = trim((string) ($payload['slug'] ?? ''));
        $discountType = trim((string) ($payload['discountType'] ?? ''));
        $discountValue = (int) ($payload['discountValue'] ?? 0);
        $audienceKey = trim((string) ($payload['audienceKey'] ?? ''));
        $criteria = isset($payload['criteria']) && \is_array($payload['criteria']) ? $payload['criteria'] : [];

        if ('' === $name || '' === $slug || '' === $discountType || '' === $audienceKey) {
            return ApiResponse::error('Champs obligatoires manquants.', Response::HTTP_BAD_REQUEST);
        }

        if (!\in_array($discountType, [Promotion::TYPE_PERCENT, Promotion::TYPE_FIXED_CENTS], true)) {
            return ApiResponse::error('Type de remise invalide.', Response::HTTP_BAD_REQUEST);
        }

        if ($discountValue <= 0) {
            return ApiResponse::error('La valeur de remise doit être supérieure à zéro.', Response::HTTP_BAD_REQUEST);
        }

        $promotion
            ->setName($name)
            ->setSlug($slug)
            ->setDescription(isset($payload['description']) ? trim((string) $payload['description']) : null)
            ->setDiscountType($discountType)
            ->setDiscountValue($discountValue)
            ->setAudienceKey($audienceKey)
            ->setCriteria($criteria)
            ->setIsActive((bool) ($payload['isActive'] ?? true))
            ->setStartsAt($this->parseDate($payload['startsAt'] ?? null))
            ->setEndsAt($this->parseDate($payload['endsAt'] ?? null));

        $this->entityManager->persist($promotion);
        $this->entityManager->flush();

        return ApiResponse::success([
            'promotion' => PromotionFormatter::formatPromotion($promotion),
        ]);
    }

    private function parseDate(mixed $value): ?\DateTimeImmutable
    {
        if (!\is_string($value) || '' === trim($value)) {
            return null;
        }

        try {
            return new \DateTimeImmutable($value);
        } catch (\Throwable) {
            return null;
        }
    }
}
