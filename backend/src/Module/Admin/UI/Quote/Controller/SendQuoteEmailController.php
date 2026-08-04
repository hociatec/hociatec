<?php

declare(strict_types=1);

namespace App\Module\Admin\UI\Quote\Controller;

use App\Module\Admin\Application\Quote\DTO\QuoteEmailInput;
use App\Module\Quote\Application\Mapper\QuoteStatusTranslator;
use App\Module\Quote\Application\Workflow\QuoteEmailService;
use App\Module\Quote\Application\Workflow\QuoteWorkflowService;
use App\Module\Quote\Domain\Entity\Quote;
use App\Module\Quote\Infrastructure\Repository\QuoteRepository;
use App\Shared\Infrastructure\Http\ApiResponse;
use App\Shared\Infrastructure\Http\InvalidJsonPayloadException;
use App\Shared\Infrastructure\Validation\DtoValidator;
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
        private readonly QuoteWorkflowService $workflow,
        private readonly LoggerInterface $logger,
        private readonly DtoValidator $validator,
    ) {
    }

    public function __invoke(Request $request, string $id): JsonResponse
    {
        $quoteId = (int) $id;
        if ($quoteId <= 0) {
            return ApiResponse::error('Devis introuvable.', Response::HTTP_NOT_FOUND);
        }

        $quote = $this->quoteRepository->find($quoteId);
        if (null === $quote) {
            return ApiResponse::error('Devis introuvable.', Response::HTTP_NOT_FOUND);
        }

        try {
            $payload = \App\Shared\Infrastructure\Http\JsonRequestInput::payload($request);
        } catch (InvalidJsonPayloadException|\JsonException) {
            return ApiResponse::error('Payload invalide.', Response::HTTP_BAD_REQUEST);
        }

        try {
            $input = QuoteEmailInput::fromArray($payload);
            $this->validator->validate($input);
            $result = $this->quoteEmailService->send($quote, $input->to?->value());
        } catch (\InvalidArgumentException $exception) {
            return ApiResponse::error($exception->getMessage(), Response::HTTP_BAD_REQUEST);
        } catch (\RuntimeException $exception) {
            return ApiResponse::error($exception->getMessage(), Response::HTTP_SERVICE_UNAVAILABLE);
        } catch (\LogicException $exception) {
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

        $this->workflow->setStatus($quote, Quote::STATUS_SENT);

        return ApiResponse::success([
            'sent' => true,
            'statusCode' => Quote::STATUS_SENT,
            'statusLabel' => QuoteStatusTranslator::toLabel(Quote::STATUS_SENT),
            'to' => $result['to'],
            'attachmentIncluded' => $result['attachmentIncluded'],
            'transport' => $result['transport'],
            'message' => 'outbox' === $result['transport']
                ? 'Envoi du devis programmé.'
                : ($result['attachmentIncluded']
                ? 'Devis envoyé par e-mail avec le PDF en pièce jointe.'
                : 'Devis envoyé par e-mail.'),
        ]);
    }
}
