<?php

declare(strict_types=1);

namespace App\Module\TradeIn\UI\Controller;

use App\Module\TradeIn\Application\Workflow\CustomerTradeInPortalService;
use App\Shared\Infrastructure\Http\AuthenticatedDomainUserTrait;
use App\Shared\Infrastructure\Http\AttachmentResponseFactory;
use App\Shared\Infrastructure\Http\ApiResponse;
use App\Shared\Infrastructure\Http\RateLimitKeyFactory;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\DependencyInjection\Attribute\Autowire;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\RateLimiter\RateLimiterFactory;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route('/api/trade-ins/{id}/receipt', name: 'api_trade_ins_receipt', methods: ['GET'])]
#[IsGranted('ROLE_USER')]
final class DownloadMyTradeInReceiptController extends AbstractController
{
    use AuthenticatedDomainUserTrait;

    public function __construct(
        private readonly CustomerTradeInPortalService $portal,
        private readonly AttachmentResponseFactory $attachments,
        private readonly RateLimitKeyFactory $rateLimitKeys,
        #[Autowire(service: 'limiter.private_file_download')]
        private readonly RateLimiterFactory $limiter,
    ) {
    }

    public function __invoke(int $id, Request $request): Response
    {
        $user = $this->currentUser();
        $limit = $this->limiter->create($this->rateLimitKeys->forRequest($request, $user->getEmail().':tradein-receipt'))->consume(1);
        if (!$limit->isAccepted()) {
            return ApiResponse::error('Trop de téléchargements privés. Veuillez réessayer plus tard.', Response::HTTP_TOO_MANY_REQUESTS);
        }

        $receipt = $this->portal->downloadReceiptForUser($user, $id);
        if (null === $receipt) {
            throw $this->createNotFoundException('Justificatif indisponible.');
        }

        return $this->attachments->create($receipt['content'], $receipt['filename'], 'application/pdf');
    }
}
