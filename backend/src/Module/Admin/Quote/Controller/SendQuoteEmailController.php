<?php

declare(strict_types=1);

namespace App\Module\Admin\Quote\Controller;

use App\Module\Quote\Repository\QuoteRepository;
use App\Module\Quote\Entity\Quote;
use Doctrine\ORM\EntityManagerInterface;
use App\Module\Quote\Service\QuoteEmailService;
use App\Shared\Http\ApiResponse;
use Psr\Log\LoggerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route(
    '/api/admin/quotes/{id}/send-email',
    name: 'api_admin_quotes_send_email',
    methods: ['POST'],
    requirements: ['id' => '\d+']
)]
#[IsGranted('ROLE_ADMIN')]
class SendQuoteEmailController extends AbstractController
{
    public function __construct(
        private readonly QuoteRepository $quoteRepository,
        private readonly QuoteEmailService $quoteEmailService,
        private readonly EntityManagerInterface $em,
        private readonly LoggerInterface $logger,
    ) {
    }

    public function __invoke(Request $request, string $id): JsonResponse
    {
        $quoteId = (int) $id;
        if ($quoteId <= 0) {
            return ApiResponse::error('Devis introuvable.', Response::HTTP_NOT_FOUND);
        }

        $quote = $this->quoteRepository->find($quoteId);
        if ($quote === null) {
            return ApiResponse::error('Devis introuvable.', Response::HTTP_NOT_FOUND);
        }

        try {
            $payload = $request->toArray();
        } catch (\Throwable) {
            return ApiResponse::error('Payload invalide.', Response::HTTP_BAD_REQUEST);
        }

        try {
            $result = $this->quoteEmailService->send($quote, isset($payload['to']) ? (string) $payload['to'] : null);
        } catch (\InvalidArgumentException $exception) {
            return ApiResponse::error($exception->getMessage(), Response::HTTP_BAD_REQUEST);
        } catch (\RuntimeException $exception) {
            return ApiResponse::error($exception->getMessage(), Response::HTTP_SERVICE_UNAVAILABLE);
        } catch (\Throwable $exception) {
            $this->logger->error('Unexpected quote email send failure.', [
                'quoteId' => $quote->getId(),
                'quoteNumber' => $quote->getNumber(),
                'exception' => $exception,
            ]);

            return ApiResponse::error(
                'Envoi impossible pour le moment. Vérifie la configuration email ou consulte les logs serveur.',
                Response::HTTP_SERVICE_UNAVAILABLE
            );
        }

        $quote->setStatus(Quote::STATUS_SENT);
        $quote->setCreatedEmailSentAt(new \DateTimeImmutable());
        $this->em->flush();

        return ApiResponse::success([
            'sent' => true,
            'to' => $result['to'],
            'attachmentIncluded' => $result['attachmentIncluded'],
            'transport' => $result['transport'],
            'message' => $result['attachmentIncluded']
                ? 'Devis envoyé par e-mail avec le PDF en pièce jointe.'
                : 'Devis envoyé par e-mail.',
        ]);
    }
}
