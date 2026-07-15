<?php

declare(strict_types=1);

namespace App\Module\Admin\Promotion\Controller;

use App\Module\Promotion\Entity\Promotion;
use App\Module\Promotion\Service\PromotionFormatter;
use App\Shared\Http\ApiResponse;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route('/api/admin/promotions', name: 'api_admin_promotions_create', methods: ['POST'])]
#[IsGranted('ROLE_ADMIN')]
final class CreatePromotionController extends AbstractController
{
    public function __construct(private readonly EntityManagerInterface $entityManager)
    {
    }

    public function __invoke(Request $request): JsonResponse
    {
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

        if ($name === '' || $slug === '' || $discountType === '' || $audienceKey === '') {
            return ApiResponse::error('Champs obligatoires manquants.', Response::HTTP_BAD_REQUEST);
        }

        if (!\in_array($discountType, [Promotion::TYPE_PERCENT, Promotion::TYPE_FIXED_CENTS], true)) {
            return ApiResponse::error('Type de remise invalide.', Response::HTTP_BAD_REQUEST);
        }

        if ($discountValue <= 0) {
            return ApiResponse::error('La valeur de remise doit être supérieure à zéro.', Response::HTTP_BAD_REQUEST);
        }

        $promotion = new Promotion($name, $slug, $discountType, $discountValue, $audienceKey, $criteria);
        $promotion
            ->setDescription(isset($payload['description']) ? trim((string) $payload['description']) : null)
            ->setIsActive((bool) ($payload['isActive'] ?? true))
            ->setStartsAt($this->parseDate($payload['startsAt'] ?? null))
            ->setEndsAt($this->parseDate($payload['endsAt'] ?? null));

        $this->entityManager->persist($promotion);
        $this->entityManager->flush();

        return ApiResponse::created([
            'promotion' => PromotionFormatter::formatPromotion($promotion),
        ]);
    }

    private function parseDate(mixed $value): ?\DateTimeImmutable
    {
        if (!\is_string($value) || trim($value) === '') {
            return null;
        }

        try {
            return new \DateTimeImmutable($value);
        } catch (\Throwable) {
            return null;
        }
    }
}
