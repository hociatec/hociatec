<?php

declare(strict_types=1);

namespace App\Module\TradeIn\UI\Controller;

use App\Module\TradeIn\Application\Port\TradeInRequestRepositoryPort;
use App\Module\TradeIn\Application\Storage\TradeInPrivateFileStorage;
use App\Module\TradeIn\Domain\Security\TradeInAccessPolicy;
use App\Module\User\Domain\Entity\User;
use App\Shared\Infrastructure\Http\AttachmentResponseFactory;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route('/api/trade-ins/{id}/receipt', name: 'api_trade_ins_receipt', methods: ['GET'])]
#[IsGranted('ROLE_USER')]
final class DownloadMyTradeInReceiptController extends AbstractController
{
    public function __construct(
        private readonly TradeInRequestRepositoryPort $requests,
        private readonly TradeInPrivateFileStorage $files,
        private readonly TradeInAccessPolicy $accessPolicy,
        private readonly AttachmentResponseFactory $attachments,
    ) {
    }

    public function __invoke(int $id): Response
    {
        /** @var User $user */
        $user = $this->getUser();
        $request = $this->requests->find($id);
        if (null === $request || !$this->accessPolicy->canDownloadReceipt($user, $request)) {
            throw $this->createNotFoundException('Justificatif indisponible.');
        }

        $receiptPath = $request->getReceiptPath();
        if (null === $receiptPath) {
            throw $this->createNotFoundException('Justificatif indisponible.');
        }

        return $this->attachments->create($this->files->read($receiptPath), 'justificatif-reprise-'.$request->getReference().'.pdf', 'application/pdf');
    }
}
