<?php

declare(strict_types=1);

namespace App\Module\Admin\TradeIn\Controller;

use App\Module\TradeIn\Repository\TradeInRequestRepository;
use App\Module\TradeIn\Service\TradeInPrivateFileStorage;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\ResponseHeaderBag;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route('/api/admin/trade-ins/{id}/{document}', name: 'api_admin_trade_ins_document', requirements: ['document' => 'rib|receipt'], methods: ['GET'])]
#[IsGranted('ROLE_ADMIN')]
final class DownloadTradeInDocumentController extends AbstractController
{
    public function __construct(private readonly TradeInRequestRepository $requests, private readonly TradeInPrivateFileStorage $files)
    {
    }

    public function __invoke(int $id, string $document): Response
    {
        $request = $this->requests->find($id);
        if (null === $request) {
            throw $this->createNotFoundException('Demande de reprise introuvable.');
        }
        $path = 'rib' === $document ? $request->getRibPath() : $request->getReceiptPath();
        if (null === $path) {
            throw $this->createNotFoundException('Document indisponible.');
        }

        $response = new Response($this->files->read($path), Response::HTTP_OK, ['Content-Type' => 'application/pdf', 'X-Content-Type-Options' => 'nosniff']);
        $response->headers->set('Content-Disposition', $response->headers->makeDisposition(ResponseHeaderBag::DISPOSITION_ATTACHMENT, 'receipt' === $document ? 'justificatif-reprise.pdf' : 'rib-demandeur.pdf'));

        return $response;
    }
}
