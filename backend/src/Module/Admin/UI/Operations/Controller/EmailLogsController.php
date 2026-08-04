<?php

declare(strict_types=1);

namespace App\Module\Admin\UI\Operations\Controller;

use App\Infrastructure\Http\ApiResponse;
use App\Module\Admin\Application\Operations\Projection\AdminOperationsFormatter;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route('/api/admin/operations/email-logs', name: 'api_admin_operations_email_logs', methods: ['GET'])]
#[IsGranted('ROLE_OPERATIONS')]
final readonly class EmailLogsController
{
    public function __construct(private AdminOperationsFormatter $formatter)
    {
    }

    public function __invoke(): JsonResponse
    {
        return ApiResponse::success(['items' => $this->formatter->emailLogs()]);
    }
}
