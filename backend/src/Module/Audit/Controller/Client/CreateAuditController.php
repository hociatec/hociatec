<?php

declare(strict_types=1);

namespace App\Module\Audit\Controller\Client;

use App\Module\Audit\Entity\AuditType;
use App\Module\Audit\Dto\CreateAuditRequestDto;
use App\Module\Audit\Service\CreateAuditRequestService;
use App\Module\User\Entity\User;
use App\Shared\Http\ApiResponse;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;
use Symfony\Component\Validator\Validator\ValidatorInterface;

#[Route('/api/audits', name: 'api_audits_create', methods: ['POST'])]
#[IsGranted('ROLE_USER')]
class CreateAuditController extends AbstractController
{
    public function __construct(
        private readonly CreateAuditRequestService $service,
        private readonly ValidatorInterface $validator,
    ) {}

    public function __invoke(Request $request): JsonResponse
    {
        /** @var User $user */
        $user = $this->getUser();
        $payload = json_decode((string) $request->getContent(), true);
        if (!is_array($payload)) {
            return ApiResponse::error('Requête invalide.');
        }

        $dto = new CreateAuditRequestDto();
        $dto->type = (string) ($payload['type'] ?? '');
        $dto->url = trim((string) ($payload['url'] ?? ''));
        $dto->objectives = array_key_exists('objectives', $payload) ? (string) $payload['objectives'] : null;

        $violations = $this->validator->validate($dto);
        if ($violations->count() > 0) {
            $errors = [];
            foreach ($violations as $v) {
                $errors[] = sprintf('%s: %s', (string) $v->getPropertyPath(), (string) $v->getMessage());
            }
            return ApiResponse::error('Requête invalide.', 400, $errors);
        }

        $type = AuditType::tryFrom($dto->type);
        if ($type === null) {
            return ApiResponse::error('Type d\'audit invalide.');
        }

        $audit = $this->service->create($user, $type, $dto->url, $dto->objectives);

        return ApiResponse::created([
            'id' => $audit->getId(),
            'number' => $audit->getNumber(),
        ]);
    }
}
