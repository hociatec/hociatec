<?php

declare(strict_types=1);

namespace App\Module\Admin\Quote\Controller;

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

#[Route(
    '/api/admin/quotes/services/{id}',
    name: 'api_admin_quotes_services_update',
    methods: ['POST', 'PUT', 'PATCH'],
    requirements: ['id' => '\d+']
)]
#[IsGranted('ROLE_ADMIN')]
class UpdateServiceController extends AbstractController
{
    public function __construct(
        private readonly EntityManagerInterface $em,
        private readonly ServiceRepository $serviceRepository,
    ) {
    }

    public function __invoke(Request $request, int $id): JsonResponse
    {
        $service = $this->serviceRepository->find($id);
        if ($service === null) {
            return ApiResponse::error('Service introuvable.', Response::HTTP_NOT_FOUND);
        }

        $title = trim((string) ($request->request->get('title') ?? $service->getTitle()));
        $description = $request->request->get('description');
        $unit = $request->request->get('unit');
        $priceRaw = $request->request->get('price');
        $vatRaw = $request->request->get('vatRate');

        if ($title === '') {
            return ApiResponse::error('Titre invalide.', Response::HTTP_UNPROCESSABLE_ENTITY);
        }

        try {
            $service->setTitle($title);
            $service->setDescription($description !== '' ? (string) $description : null);
            $service->setUnit($unit !== '' ? (string) $unit : null);

            if ($priceRaw !== null) {
                $price = $this->normalizePriceToCents($priceRaw);
                if ($price < 0) {
                    return ApiResponse::error('Prix invalide.', Response::HTTP_UNPROCESSABLE_ENTITY);
                }
                $service->setPriceCents($price);
            }

            if ($vatRaw !== null) {
                $service->setVatRateBps($this->normalizeVatToBps($vatRaw));
            }

            $this->em->flush();
        } catch (Throwable $e) {
            return ApiResponse::error('Impossible de mettre a jour le service.', Response::HTTP_BAD_REQUEST, [$e->getMessage()]);
        }

        return ApiResponse::success(QuoteFormatter::formatService($service));
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
