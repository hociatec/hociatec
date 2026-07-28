<?php

declare(strict_types=1);

namespace App\Module\Admin\BetaTest\Controller;

use App\Module\BetaTest\Entity\BetaCampaign;
use App\Shared\Http\ApiResponse;
use App\Shared\Http\JsonPayload;
use App\Shared\Persistence\DoctrinePersistence;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route('/api/admin/beta-campaigns', methods: ['POST'])] #[IsGranted('ROLE_ADMIN')]
final class CreateCampaignController extends AbstractController
{
    public function __construct(private readonly DoctrinePersistence $persistence)
    {
    }

    public function __invoke(Request $request): JsonResponse
    {
        $p = JsonPayload::decode($request);
        $name = trim((string) ($p['name'] ?? ''));
        $description = trim((string) ($p['description'] ?? ''));
        if ('' === $name || '' === $description) {
            return ApiResponse::error('Le nom et la description sont obligatoires.', 422);
        }

        $startsAt = $this->dateFromPayload($p['startsAt'] ?? null) ?? new \DateTimeImmutable('today');
        $endsAt = $this->dateFromPayload($p['endsAt'] ?? null) ?? $startsAt->modify('+30 days');
        if (null !== $startsAt && null !== $endsAt && $endsAt < $startsAt) {
            return ApiResponse::error('La date de fin doit être postérieure à la date de début.', 422);
        }

        $campaign = new BetaCampaign($name, $description, $startsAt, $endsAt);
        $campaign->setStatus(in_array($p['status'] ?? 'draft', ['draft', 'active', 'closed'], true) ? (string) $p['status'] : 'draft');
        $this->persistence->persist($campaign);
        $this->persistence->flush();

        return ApiResponse::created(['id' => $campaign->getId()], 'Campagne créée.');
    }

    private function dateFromPayload(mixed $value): ?\DateTimeImmutable
    {
        if (!is_string($value) || '' === trim($value)) {
            return null;
        }

        $date = \DateTimeImmutable::createFromFormat('!Y-m-d', trim($value));

        return $date instanceof \DateTimeImmutable ? $date : null;
    }
}
