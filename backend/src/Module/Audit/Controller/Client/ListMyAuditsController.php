<?php

declare(strict_types=1);

namespace App\Module\Audit\Controller\Client;

use App\Module\Audit\Repository\AuditRequestRepository;
use App\Module\User\Entity\User;
use App\Shared\Http\ApiResponse;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route('/api/audits', name: 'api_audits_list_mine', methods: ['GET'])]
#[IsGranted('ROLE_USER')]
class ListMyAuditsController extends AbstractController
{
    public function __construct(private readonly AuditRequestRepository $repository)
    {
    }

    public function __invoke(): JsonResponse
    {
        /** @var User $user */
        $user = $this->getUser();
        $items = $this->repository->findByUser($user);

        return ApiResponse::success([
            'items' => array_map(static function ($a) {
                return [
                    'id' => $a->getId(),
                    'number' => $a->getNumber(),
                    'type' => $a->getType()->value,
                    'status' => $a->getStatus(),
                    'url' => $a->getTargetUrl(),
                    'createdAt' => $a->getCreatedAt()->format(DATE_ATOM),
                ];
            }, $items),
        ]);
    }
}
