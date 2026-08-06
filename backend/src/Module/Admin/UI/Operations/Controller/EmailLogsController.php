<?php

declare(strict_types=1);

namespace App\Module\Admin\UI\Operations\Controller;

use App\Module\Admin\Application\Operations\Projection\AdminOperationsFormatter;
use App\Shared\Infrastructure\Http\ApiResponse;
use App\Shared\Infrastructure\Http\RequestQueryMapper;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route('/api/admin/operations/email-logs', name: 'api_admin_operations_email_logs', methods: ['GET'])]
#[IsGranted('ROLE_OPERATIONS')]
final readonly class EmailLogsController
{
    public function __construct(private AdminOperationsFormatter $formatter)
    {
    }

    public function __invoke(Request $request): JsonResponse
    {
        $pagination = RequestQueryMapper::pagination($request, 10, 50);
        $items = $this->formatter->emailLogs();

        return ApiResponse::paginated(
            array_slice($items, $pagination->offset(), $pagination->perPage),
            $pagination->metadata(count($items)),
        );
    }
}
