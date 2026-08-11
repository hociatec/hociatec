<?php

declare(strict_types=1);

namespace App\Module\TradeIn\UI\Controller;

use App\Module\TradeIn\Application\Workflow\CustomerTradeInPortalService;
use App\Shared\Infrastructure\Http\AuthenticatedDomainUserTrait;
use App\Shared\Infrastructure\Http\AttachmentResponseFactory;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route('/api/trade-ins/{id}/receipt', name: 'api_trade_ins_receipt', methods: ['GET'])]
#[IsGranted('ROLE_USER')]
final class DownloadMyTradeInReceiptController extends AbstractController
{
    use AuthenticatedDomainUserTrait;

    public function __construct(
        private readonly CustomerTradeInPortalService $portal,
        private readonly AttachmentResponseFactory $attachments,
    ) {
    }

    public function __invoke(int $id): Response
    {
        $receipt = $this->portal->downloadReceiptForUser($this->currentUser(), $id);
        if (null === $receipt) {
            throw $this->createNotFoundException('Justificatif indisponible.');
        }

        return $this->attachments->create($receipt['content'], $receipt['filename'], 'application/pdf');
    }
}
