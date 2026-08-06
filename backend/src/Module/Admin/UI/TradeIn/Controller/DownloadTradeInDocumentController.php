<?php

declare(strict_types=1);

namespace App\Module\Admin\UI\TradeIn\Controller;

use App\Module\TradeIn\Application\Port\TradeInPrivateFileStoragePort;
use App\Module\TradeIn\Application\Port\TradeInRequestRepositoryPort;
use App\Shared\Infrastructure\Http\AttachmentResponseFactory;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route('/api/admin/trade-ins/{id}/{document}', name: 'api_admin_trade_ins_document', requirements: ['document' => 'rib|receipt'], methods: ['GET'])]
#[IsGranted('ROLE_TRADE_INS_MANAGER')]
final class DownloadTradeInDocumentController extends AbstractController
{
    public function __construct(
        private readonly TradeInRequestRepositoryPort $requests,
        private readonly TradeInPrivateFileStoragePort $files,
        private readonly AttachmentResponseFactory $attachments,
    ) {
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

        return $this->attachments->create($this->files->read($path), 'receipt' === $document ? 'justificatif-reprise.pdf' : 'rib-demandeur.pdf', 'application/pdf');
    }
}
