<?php

declare(strict_types=1);

namespace App\Module\Audit\Controller\Client;

use App\Module\Audit\Entity\AuditType;
use App\Module\Audit\Service\CreateAuditRequestService;
use App\Module\User\Entity\User;
use App\Shared\Http\ApiResponse;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route('/api/audits', name: 'api_audits_create', methods: ['POST'])]
#[IsGranted('ROLE_USER')]
class CreateAuditController extends AbstractController
{
    public function __construct(private readonly CreateAuditRequestService $service) {}

    public function __invoke(Request $request): JsonResponse
    {
        /** @var User $user */
        $user = $this->getUser();
        $payload = json_decode((string) $request->getContent(), true);
        if (!is_array($payload)) {
            return ApiResponse::error('Requête invalide.');
        }

        $typeStr = (string) ($payload['type'] ?? '');
        $url = trim((string) ($payload['url'] ?? ''));
        $objectives = isset($payload['objectives']) ? (string) $payload['objectives'] : null;

        $type = AuditType::tryFrom($typeStr);
        if ($type === null) {
            return ApiResponse::error('Type d\'audit invalide.');
        }
        if ($url === '') {
            return ApiResponse::error('URL cible requise.');
        }

        $audit = $this->service->create($user, $type, $url, $objectives);

        return ApiResponse::created([
            'id' => $audit->getId(),
            'number' => $audit->getNumber(),
        ]);
    }
}

