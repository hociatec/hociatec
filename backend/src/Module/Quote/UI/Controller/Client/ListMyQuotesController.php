<?php

declare(strict_types=1);

namespace App\Module\Quote\UI\Controller\Client;

// not used, rely on AbstractController
use App\Infrastructure\Http\ApiResponse;
use App\Module\Quote\Application\Projection\QuoteFormatter;
use App\Module\Quote\Application\Service\QuoteCalculator;
use App\Module\Quote\Infrastructure\Repository\QuoteRepository;
use App\Module\User\Domain\Entity\User;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route('/api/quotes/me', name: 'api_quotes_me', methods: ['GET'])]
#[IsGranted('ROLE_USER')]
class ListMyQuotesController extends AbstractController
{
    public function __construct(
        private readonly QuoteRepository $quotes,
        private readonly QuoteCalculator $calculator,
    ) {
    }

    public function __invoke(): JsonResponse
    {
        /** @var User $user */
        $user = $this->getUser();
        $email = $user->getEmail();

        $qb = $this->quotes->createQueryBuilder('q')
            ->andWhere('LOWER(q.customerEmail) = LOWER(:email)')
            ->setParameter('email', $email)
            ->orderBy('q.createdAt', 'DESC');

        $items = array_map(
            fn ($q) => QuoteFormatter::formatQuote($q, $this->calculator),
            $qb->getQuery()->getResult(),
        );

        return ApiResponse::successItem('items', $items);
    }
}
