<?php

declare(strict_types=1);

namespace App\Module\Audit\UI\Controller\Client;

use App\Infrastructure\Http\ApiResponse;
use App\Module\Audit\Application\Service\AuditMetadataFormatter;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route('/api/audits/metadata', name: 'api_audits_metadata', methods: ['GET'])]
#[IsGranted('ROLE_USER')]
final class ListAuditMetadataController extends AbstractController
{
    public function __construct(private readonly AuditMetadataFormatter $formatter)
    {
    }

    public function __invoke(): JsonResponse
    {
        return ApiResponse::success([
            'types' => $this->formatter->types(),
            'statuses' => $this->formatter->statuses(),
        ]);
    }
}
