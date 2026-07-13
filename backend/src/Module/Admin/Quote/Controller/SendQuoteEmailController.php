<?php

declare(strict_types=1);

namespace App\Module\Admin\Quote\Controller;

use App\Module\Quote\Repository\QuoteRepository;
use App\Shared\Http\ApiResponse;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route('/api/admin/quotes/{id}/send-email', name: 'api_admin_quotes_send_email', methods: ['POST'])]
#[IsGranted('ROLE_ADMIN')]
class SendQuoteEmailController extends AbstractController
{
    public function __construct(private readonly QuoteRepository $quoteRepository)
    {
    }

    public function __invoke(Request $request, int $id): JsonResponse
    {
        $quote = $this->quoteRepository->find($id);
        if ($quote === null) {
            return ApiResponse::error('Devis introuvable.', Response::HTTP_NOT_FOUND);
        }

        // This project currently does not include symfony/mailer in require.
        // Provide a helpful response until mailer is installed and configured.
        $email = $request->toArray()['to'] ?? $quote->getCustomerEmail();

        return ApiResponse::success([
            'sent' => false,
            'to' => $email,
            'message' => 'Envoi e-mail indisponible: ajouter symfony/mailer et configurer un transport.',
        ], Response::HTTP_NOT_IMPLEMENTED);
    }
}

