<?php

declare(strict_types=1);

namespace App\Module\Quote\Controller\Client;

use App\Module\Quote\Repository\QuoteRepository;
use App\Module\User\Entity\User;
use App\Shared\Http\ApiResponse;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route('/api/quotes/me/{id}', name: 'api_quotes_me_delete', methods: ['DELETE'])]
#[IsGranted('ROLE_USER')]
class DeleteMyQuoteController extends AbstractController
{
    public function __construct(
        private readonly QuoteRepository $quotes,
        private readonly EntityManagerInterface $em,
    ) {
    }

    public function __invoke(int $id): JsonResponse
    {
        /** @var User $user */
        $user = $this->getUser();
        $email = $user->getEmail();

        $quote = $this->quotes->find($id);
        if (null === $quote || (string) strtolower((string) $quote->getCustomerEmail()) !== strtolower((string) $email)) {
            return ApiResponse::error('Devis introuvable.', Response::HTTP_NOT_FOUND);
        }

        $this->em->remove($quote);
        $this->em->flush();

        return ApiResponse::success(['deleted' => true]);
    }
}
