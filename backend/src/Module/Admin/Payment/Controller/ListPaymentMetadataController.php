<?php

declare(strict_types=1);

namespace App\Module\Admin\Payment\Controller;

use App\Module\Admin\Payment\Service\AdminPaymentFormatter;
use App\Shared\Http\ApiResponse;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route('/api/admin/payments/metadata', name: 'api_admin_payments_metadata', methods: ['GET'])]
#[IsGranted('ROLE_ADMIN')]
final class ListPaymentMetadataController extends AbstractController
{
    public function __construct(private readonly AdminPaymentFormatter $formatter)
    {
    }

    public function __invoke(): JsonResponse
    {
        return ApiResponse::success(['statuses' => $this->formatter->statusOptions()]);
    }
}
