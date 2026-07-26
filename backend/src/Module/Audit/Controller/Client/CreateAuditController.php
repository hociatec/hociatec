<?php

declare(strict_types=1);

namespace App\Module\Audit\Controller\Client;

use App\Module\Audit\Dto\CreateAuditRequestDto;
use App\Module\Audit\Entity\AuditType;
use App\Module\Audit\Service\AuditEventLogger;
use App\Module\Audit\Service\CreateAuditRequestService;
use App\Module\User\Entity\User;
use App\Shared\Http\ApiResponse;
use App\Shared\Validation\DtoValidator;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route('/api/audits', name: 'api_audits_create', methods: ['POST'])]
#[IsGranted('ROLE_USER')]
class CreateAuditController extends AbstractController
{
    public function __construct(
        private readonly CreateAuditRequestService $service,
        private readonly AuditEventLogger $events,
        private readonly DtoValidator $dtoValidator,
    ) {
    }

    public function __invoke(Request $request): JsonResponse
    {
        /** @var User $user */
        $user = $this->getUser();
        $payload = \App\Shared\Http\JsonPayload::decode($request);
        $dto = new CreateAuditRequestDto();
        $dto->type = (string) ($payload['type'] ?? '');
        $dto->url = trim((string) ($payload['url'] ?? ''));
        $dto->objectives = array_key_exists('objectives', $payload) ? (string) $payload['objectives'] : null;

        $this->dtoValidator->validate($dto, message: 'Requête invalide.', statusCode: JsonResponse::HTTP_BAD_REQUEST);

        $type = AuditType::tryFrom($dto->type);
        if (null === $type) {
            return ApiResponse::error('Type d\'audit invalide.');
        }

        $audit = $this->service->create($user, $type, $dto->url, $dto->objectives);

        // Log event
        $this->events->log($audit, $user, 'created', 'Demande d\'audit créée');

        return ApiResponse::created([
            'id' => $audit->getId(),
            'number' => $audit->getNumber(),
        ]);
    }
}
