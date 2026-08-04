<?php

declare(strict_types=1);

namespace App\Module\Audit\UI\Controller\Client;

use App\Infrastructure\Http\ApiResponse;
use App\Infrastructure\Validation\DtoValidator;
use App\Module\Audit\Application\DTO\CreateAuditRequestDto;
use App\Module\Audit\Application\Service\AuditEventLogger;
use App\Module\Audit\Application\Service\CreateAuditRequestService;
use App\Module\Audit\Domain\Entity\AuditType;
use App\Module\User\Domain\Entity\User;
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
        $payload = \App\Infrastructure\Http\JsonPayload::decode($request);
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
        ], 'Votre demande d’audit a bien été enregistrée.');
    }
}
