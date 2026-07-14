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

#[Route('/api/admin/services', name: 'api_admin_services_create', methods: ['POST'])]
#[IsGranted('ROLE_ADMIN')]
class CreateServiceController extends AbstractController
{
    private const BILLING_MODES = [
        'prix fixe',
        'heure',
        'jour',
        'intervention',
        'audit',
        'installation',
        'maintenance',
    ];

    public function __construct(
        private readonly EntityManagerInterface $em,
        private readonly ServiceRepository $serviceRepository,
    ) {
    }

    public function __invoke(Request $request): JsonResponse
    {
        $title = trim((string) $request->request->get('title', ''));
        $description = $request->request->get('description');
        $unit = $this->normalizeBillingMode($request->request->get('unit'));
        $durationValue = $this->normalizeDurationValue($request->request->get('durationValue'));
        $durationUnit = $this->normalizeDurationUnit($request->request->get('durationUnit'));
        $priceRaw = $request->request->get('price');
        $vatRaw = $request->request->get('vatRate');

        $priceCents = $this->normalizePriceToCents($priceRaw);
        $vatBps = $this->normalizeVatToBps($vatRaw);

        if ($title === '' || $priceCents < 0) {
            return ApiResponse::error('Titre ou prix invalide.', Response::HTTP_UNPROCESSABLE_ENTITY);
        }

        if ($unit === null) {
            return ApiResponse::error('Mode de facturation invalide.', Response::HTTP_UNPROCESSABLE_ENTITY);
        }

        if (($durationValue === null) !== ($durationUnit === null)) {
            return ApiResponse::error('La durée doit contenir une valeur et une unité.', Response::HTTP_UNPROCESSABLE_ENTITY);
        }

        try {
            $service = new QuoteServiceEntity($title, $priceCents, $vatBps);
            $service->setDescription($description !== '' ? (string) $description : null);
            $service->setUnit($unit);
            $service->setDurationValue($durationValue);
            $service->setDurationUnit($durationUnit);
            $this->em->persist($service);
            $this->em->flush();
        } catch (Throwable $e) {
            return ApiResponse::error('Impossible de creer le service.', Response::HTTP_BAD_REQUEST, [$e->getMessage()]);
        }

        return ApiResponse::created(QuoteFormatter::formatService($service));
    }

    private function normalizeBillingMode(mixed $unit): ?string
    {
        if (!is_string($unit) || trim($unit) === '') {
            return 'prix fixe';
        }

        $normalized = mb_strtolower(trim($unit));

        return in_array($normalized, self::BILLING_MODES, true) ? $normalized : null;
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

    private function normalizeDurationValue(mixed $value): ?int
    {
        if ($value === null || $value === '') {
            return null;
        }

        $durationValue = (int) $value;
        return $durationValue > 0 ? $durationValue : null;
    }

    private function normalizeDurationUnit(mixed $unit): ?string
    {
        if (!is_string($unit) || $unit === '') {
            return null;
        }

        return in_array($unit, ['hour', 'day'], true) ? $unit : null;
    }
}
