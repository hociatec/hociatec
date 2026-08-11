<?php

declare(strict_types=1);

namespace App\Module\Quote\UI\Controller\Client;

use App\Module\Quote\Application\Workflow\CustomerQuotePortalService;
use App\Shared\Infrastructure\Http\ApiResponse;
use App\Shared\Infrastructure\Http\AttachmentResponseFactory;
use App\Shared\Infrastructure\Http\AuthenticatedDomainUserTrait;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route('/api/quotes/me/{id}/pdf', name: 'api_quotes_me_generate_pdf', methods: ['POST'])]
#[IsGranted('ROLE_USER')]
class GenerateMyQuotePdfController extends AbstractController
{
    use AuthenticatedDomainUserTrait;

    public function __construct(
        private readonly CustomerQuotePortalService $portal,
        private readonly AttachmentResponseFactory $attachments,
    ) {
    }

    public function __invoke(int $id): Response
    {
        try {
            $pdf = $this->portal->renderPdfForUser($this->currentUser(), $id);
        } catch (\RuntimeException) {
            return ApiResponse::error(
                'Génération PDF accessible indisponible.',
                Response::HTTP_NOT_IMPLEMENTED
            );
        }
        if (null === $pdf) {
            return ApiResponse::error('Devis introuvable.', Response::HTTP_NOT_FOUND);
        }

        return $this->attachments->create($pdf['content'], $pdf['filename'], 'application/pdf');
    }
}
