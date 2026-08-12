<?php

declare(strict_types=1);

namespace App\Module\Support\UI\Controller;

use App\Module\Support\Application\Workflow\CustomerSupportPortalService;
use App\Shared\Infrastructure\Http\ApiResponse;
use App\Shared\Infrastructure\Http\AuthenticatedDomainUserTrait;
use App\Shared\Infrastructure\Http\RequestQueryMapper;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route('/api/support/me', name: 'api_support_me_list', methods: ['GET'])]
#[IsGranted('ROLE_USER')]
final class ListMySupportRequestsController extends AbstractController
{
    use AuthenticatedDomainUserTrait;

    public function __construct(private readonly CustomerSupportPortalService $portal)
    {
    }

    public function __invoke(?Request $request = null): JsonResponse
    {
        $request ??= new Request();
        $pagination = RequestQueryMapper::pagination($request, 10, 50);
        $result = $this->portal->listForUser($this->currentUser(), $pagination->perPage, $pagination->offset());

        return ApiResponse::paginated($result['items'], $pagination->metadata($result['total']));
    }
}
