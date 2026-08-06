<?php

declare(strict_types=1);

namespace App\Module\Audit\UI\Controller\Client;

use App\Module\Audit\Application\Port\AuditRequestRepositoryPort;
use App\Module\Audit\Application\Projection\AuditMetadataFormatter;
use App\Module\User\Domain\Entity\User;
use App\Shared\Infrastructure\Http\ApiResponse;
use App\Shared\Infrastructure\Http\RequestQueryMapper;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route('/api/audits', name: 'api_audits_list_mine', methods: ['GET'])]
#[IsGranted('ROLE_USER')]
class ListMyAuditsController extends AbstractController
{
    public function __construct(
        private readonly AuditRequestRepositoryPort $repository,
        private readonly AuditMetadataFormatter $metadata,
    ) {
    }

    public function __invoke(?Request $request = null): JsonResponse
    {
        $request ??= new Request();
        $pagination = RequestQueryMapper::pagination($request, 10, 50);
        /** @var User $user */
        $user = $this->getUser();
        $items = $this->repository->findByUser($user, $pagination->perPage, $pagination->offset());

        return ApiResponse::paginated(
            array_map(function ($a) {
                return [
                    'id' => $a->getId(),
                    'number' => $a->getNumber(),
                    'type' => $a->getType()->value,
                    'typeLabel' => $this->metadata->typeLabel($a->getType()),
                    'status' => $a->getStatus(),
                    'statusLabel' => $this->metadata->statusLabel($a->getStatus()),
                    'url' => $a->getTargetUrl(),
                    'createdAt' => $a->getCreatedAt()->format(DATE_ATOM),
                ];
            }, $items),
            $pagination->metadata($this->repository->countByUser($user)),
        );
    }
}
