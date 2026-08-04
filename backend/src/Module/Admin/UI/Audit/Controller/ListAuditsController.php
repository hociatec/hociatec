<?php

declare(strict_types=1);

namespace App\Module\Admin\UI\Audit\Controller;

use App\Module\Audit\Application\Projection\AuditMetadataFormatter;
use App\Module\Audit\Infrastructure\Repository\AuditRequestRepository;
use App\Shared\Infrastructure\Http\ApiResponse;
use App\Shared\Infrastructure\Http\Pagination;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route('/api/admin/audits', name: 'api_admin_audits_list', methods: ['GET'])]
#[IsGranted('ROLE_ADMIN')]
class ListAuditsController extends AbstractController
{
    public function __construct(
        private readonly AuditRequestRepository $repository,
        private readonly AuditMetadataFormatter $metadata,
    ) {
    }

    public function __invoke(Request $request): JsonResponse
    {
        $pagination = Pagination::fromRequest($request);
        $items = $this->repository->findBy([], ['createdAt' => 'DESC'], $pagination->perPage, $pagination->offset());

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
                    'client' => [
                        'id' => $a->getClient()->getId(),
                        'name' => $a->getClient()->getFullName(),
                        'email' => $a->getClient()->getEmail(),
                    ],
                    'createdAt' => $a->getCreatedAt()->format(DATE_ATOM),
                ];
            }, $items),
            $pagination->metadata($this->repository->count([])),
        );
    }
}
