<?php

declare(strict_types=1);

namespace App\Module\Quote\Controller\Client;

use App\Module\Quote\Entity\Quote;
use App\Module\Quote\Repository\QuoteRepository;
use App\Module\Quote\Service\QuoteCalculator;
use App\Module\Quote\Service\QuoteFormatter;
use App\Module\User\Entity\User;
use App\Shared\Http\ApiResponse;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route('/api/quotes/me/{id}/refuse', name: 'api_quotes_me_refuse', methods: ['POST'])]
#[IsGranted('ROLE_USER')]
final class RefuseMyQuoteController extends AbstractController
{
    public function __construct(
        private readonly QuoteRepository $quotes,
        private readonly QuoteCalculator $calculator,
        private readonly EntityManagerInterface $em,
    ) {
    }

    public function __invoke(int $id): JsonResponse
    {
        /** @var User $user */
        $user = $this->getUser();
        $quote = $this->quotes->find($id);

        if (null === $quote || strtolower((string) $quote->getCustomerEmail()) !== strtolower($user->getEmail())) {
            return ApiResponse::error('Devis introuvable.', Response::HTTP_NOT_FOUND);
        }

        if (null !== $quote->getConvertedOrder()) {
            return ApiResponse::error('Ce devis est déjà converti en commande.', Response::HTTP_CONFLICT);
        }

        if (!in_array($quote->getStatus(), [Quote::STATUS_SENT, Quote::STATUS_REFUSED], true)) {
            return ApiResponse::error('Ce devis ne peut pas être refusé.', Response::HTTP_BAD_REQUEST);
        }

        $quote->setStatus(Quote::STATUS_REFUSED);
        $this->em->flush();

        return ApiResponse::success(QuoteFormatter::formatQuote($quote, $this->calculator));
    }
}
