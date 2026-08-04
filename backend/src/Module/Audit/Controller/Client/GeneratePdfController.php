<?php

declare(strict_types=1);

namespace App\Module\Audit\Controller\Client;

use App\Module\Audit\Repository\AuditRequestRepository;
use App\Module\Audit\Service\AuditEventLogger;
use App\Module\Audit\Service\AuditPdfService;
use App\Module\User\Entity\User;
use App\Shared\Http\ApiResponse;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[IsGranted('ROLE_USER')]
class GeneratePdfController extends AbstractController
{
    public function __construct(
        private readonly AuditRequestRepository $audits,
        private readonly AuditPdfService $pdf,
        private readonly AuditEventLogger $events,
    ) {
    }

    #[Route('/api/audits/{id}/pdf', name: 'api_audits_generate_pdf', methods: ['POST'])]
    public function detailed(int $id): Response
    {
        /** @var User $user */
        $user = $this->getUser();
        $audit = $this->audits->find($id);
        if (null === $audit || $audit->getClient()->getId() !== $user->getId()) {
            return ApiResponse::error('Audit introuvable.', Response::HTTP_NOT_FOUND);
        }

        try {
            $bin = $this->pdf->renderDetailed($audit);
        } catch (\Exception) {
            return ApiResponse::error('Génération PDF indisponible: installer dompdf/dompdf.', Response::HTTP_NOT_IMPLEMENTED);
        }

        /** @var User $user */
        $user = $this->getUser();
        $this->events->log($audit, $user, 'pdf_generated', 'Rapport détaillé (client)');

        $filename = sprintf('%s-rapport.pdf', $audit->getNumber());
        $response = new Response($bin);
        $response->headers->set('Content-Type', 'application/pdf');
        $response->headers->set('Content-Disposition', 'attachment; filename="'.$filename.'"');

        return $response;
    }

    #[Route('/api/audits/{id}/pdf-summary', name: 'api_audits_generate_pdf_summary', methods: ['POST'])]
    public function summary(int $id): Response
    {
        /** @var User $user */
        $user = $this->getUser();
        $audit = $this->audits->find($id);
        if (null === $audit || $audit->getClient()->getId() !== $user->getId()) {
            return ApiResponse::error('Audit introuvable.', Response::HTTP_NOT_FOUND);
        }

        try {
            $bin = $this->pdf->renderSummary($audit);
        } catch (\Exception) {
            return ApiResponse::error('Génération PDF indisponible: installer dompdf/dompdf.', Response::HTTP_NOT_IMPLEMENTED);
        }

        /** @var User $user */
        $user = $this->getUser();
        $this->events->log($audit, $user, 'pdf_generated', 'Synthèse PDF (client)');

        $filename = sprintf('%s-synthese.pdf', $audit->getNumber());
        $response = new Response($bin);
        $response->headers->set('Content-Type', 'application/pdf');
        $response->headers->set('Content-Disposition', 'attachment; filename="'.$filename.'"');

        return $response;
    }
}
