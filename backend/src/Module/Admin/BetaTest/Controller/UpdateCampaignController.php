<?php

declare(strict_types=1);

namespace App\Module\Admin\BetaTest\Controller;

use App\Module\BetaTest\Repository\BetaCampaignRepository;
use App\Shared\Http\ApiResponse;
use App\Shared\Http\JsonPayload;
use App\Shared\Persistence\DoctrinePersistence;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route('/api/admin/beta-campaigns/{id}', methods: ['PATCH', 'PUT'])]
#[IsGranted('ROLE_ADMIN')]
final class UpdateCampaignController extends AbstractController
{
    public function __construct(
        private readonly BetaCampaignRepository $campaigns,
        private readonly DoctrinePersistence $persistence,
    ) {
    }

    public function __invoke(int $id, Request $request): JsonResponse
    {
        $campaign = $this->campaigns->find($id);
        if (null === $campaign) {
            return ApiResponse::error('Campagne non trouvée.', 404);
        }

        $p = JsonPayload::decode($request);

        if (isset($p['name'])) {
            $name = trim((string) $p['name']);
            if ('' === $name) {
                return ApiResponse::error('Le nom est obligatoire.', 422);
            }
            $campaign->setName($name);
        }

        if (isset($p['description'])) {
            $description = trim((string) $p['description']);
            if ('' === $description) {
                return ApiResponse::error('La description est obligatoire.', 422);
            }
            $campaign->setDescription($description);
        }

        if (isset($p['status'])) {
            $status = (string) $p['status'];
            if (in_array($status, ['draft', 'active', 'closed'], true)) {
                $campaign->setStatus($status);
            }
        }

        if (array_key_exists('startsAt', $p)) {
            $campaign->setStartsAt($this->dateFromPayload($p['startsAt']));
        }

        if (array_key_exists('endsAt', $p)) {
            $campaign->setEndsAt($this->dateFromPayload($p['endsAt']));
        }

        if (null !== $campaign->getStartsAt() && null !== $campaign->getEndsAt() && $campaign->getEndsAt() < $campaign->getStartsAt()) {
            return ApiResponse::error('La date de fin doit être postérieure à la date de début.', 422);
        }

        $this->persistence->flush();

        return ApiResponse::success(['id' => $campaign->getId()], 200, 'Campagne mise à jour.');
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
