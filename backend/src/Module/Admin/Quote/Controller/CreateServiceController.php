<?php

declare(strict_types=1);

namespace App\Module\Admin\Quote\Controller;

use App\Module\Quote\Entity\Service as QuoteServiceEntity;
use App\Module\Quote\Repository\ServiceRepository;
use App\Module\Quote\Service\QuoteFormatter;
use App\Shared\Http\ApiResponse;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;
use Throwable;

#[Route('/api/admin/quotes/services', name: 'api_admin_quotes_services_create', methods: ['POST'])]
#[IsGranted('ROLE_ADMIN')]
class CreateServiceController extends AbstractController
{
    public function __construct(
        private readonly EntityManagerInterface $em,
        private readonly ServiceRepository $serviceRepository,
    ) {
    }

    public function __invoke(Request $request): JsonResponse
    {
        $title = trim((string) $request->request->get('title', ''));
        $description = $request->request->get('description');
        $unit = $request->request->get('unit');
        $priceRaw = $request->request->get('price');
        $vatRaw = $request->request->get('vatRate');

        $priceCents = $this->normalizePriceToCents($priceRaw);
        $vatBps = $this->normalizeVatToBps($vatRaw);

        if ($title === '' || $priceCents < 0) {
            return ApiResponse::error('Titre ou prix invalide.', Response::HTTP_UNPROCESSABLE_ENTITY);
        }

        try {
            $service = new QuoteServiceEntity($title, $priceCents, $vatBps);
            $service->setDescription($description !== '' ? (string) $description : null);
            $service->setUnit($unit !== '' ? (string) $unit : null);
            $this->em->persist($service);
            $this->em->flush();
        } catch (Throwable $e) {
            return ApiResponse::error('Impossible de creer le service.', Response::HTTP_BAD_REQUEST, [$e->getMessage()]);
        }

        return ApiResponse::created(QuoteFormatter::formatService($service));
    }

    private function normalizePriceToCents(mixed $price): int
    {
        if (is_int($price)) { return $price * 100; }
        if (is_float($price)) { return (int) round($price * 100); }
        if (is_string($price)) {
            $n = str_replace(',', '.', $price);
            if (is_numeric($n)) { return (int) round((float) $n * 100); }
        }
        return -1;
    }

    private function normalizeVatToBps(mixed $vat): int
    {
        if ($vat === null || $vat === '') { return 0; }
        if (is_int($vat)) { return $vat * 100; }
        if (is_float($vat)) { return (int) round($vat * 100); }
        if (is_string($vat)) { return (int) round(((float) str_replace(',', '.', $vat)) * 100); }
        return 0;
    }
}

