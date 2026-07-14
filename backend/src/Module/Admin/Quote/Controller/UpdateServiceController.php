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
    '/api/admin/services/{id}',
    name: 'api_admin_services_update',
    methods: ['POST', 'PUT', 'PATCH'],
    requirements: ['id' => '\d+']
)]
#[IsGranted('ROLE_ADMIN')]
class UpdateServiceController extends AbstractController
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

    public function __invoke(Request $request, int $id): JsonResponse
    {
        $service = $this->serviceRepository->find($id);
        if ($service === null) {
            return ApiResponse::error('Service introuvable.', Response::HTTP_NOT_FOUND);
        }

        $title = trim((string) ($request->request->get('title') ?? $service->getTitle()));
        $description = $request->request->get('description');
        $priceRaw = $request->request->get('price');
        $vatRaw = $request->request->get('vatRate');
        $hasDurationValue = $request->request->has('durationValue');
        $hasDurationUnit = $request->request->has('durationUnit');
        $hasBillingMode = $request->request->has('unit');

        if ($title === '') {
            return ApiResponse::error('Titre invalide.', Response::HTTP_UNPROCESSABLE_ENTITY);
        }

        try {
            $service->setTitle($title);
            $service->setDescription($description !== '' ? (string) $description : null);

            if ($hasBillingMode) {
                $unit = $this->normalizeBillingMode($request->request->get('unit'));
                if ($unit === null) {
                    return ApiResponse::error('Mode de facturation invalide.', Response::HTTP_UNPROCESSABLE_ENTITY);
                }

                $service->setUnit($unit);
            }

            if ($hasDurationValue || $hasDurationUnit) {
                $durationValue = $this->normalizeDurationValue(
                    $hasDurationValue ? $request->request->get('durationValue') : $service->getDurationValue()
                );
                $durationUnit = $this->normalizeDurationUnit(
                    $hasDurationUnit ? $request->request->get('durationUnit') : $service->getDurationUnit()
                );

                if (($durationValue === null) !== ($durationUnit === null)) {
                    return ApiResponse::error('La durée doit contenir une valeur et une unité.', Response::HTTP_UNPROCESSABLE_ENTITY);
                }

                $service->setDurationValue($durationValue);
                $service->setDurationUnit($durationUnit);
            }

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
