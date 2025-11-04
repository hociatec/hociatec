<?php

declare(strict_types=1);

namespace App\Module\Admin\Quote\Controller;

use App\Module\Catalog\Repository\ProductRepository;
use App\Module\Quote\Entity\QuoteItem;
use App\Module\Quote\Repository\QuoteRepository;
use App\Module\Quote\Service\QuoteCalculator;
use App\Module\Quote\Service\QuoteFormatter;
use App\Shared\Http\ApiResponse;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route('/api/admin/quotes/{id}/items/product', name: 'api_admin_quotes_add_product_item', methods: ['POST'])]
#[IsGranted('ROLE_ADMIN')]
class AddProductItemController extends AbstractController
{
    public function __construct(
        private readonly EntityManagerInterface $em,
        private readonly QuoteRepository $quoteRepository,
        private readonly ProductRepository $productRepository,
        private readonly QuoteCalculator $calculator,
    ) {
    }

    public function __invoke(Request $request, int $id): JsonResponse
    {
        $quote = $this->quoteRepository->find($id);
        if ($quote === null) {
            return ApiResponse::error('Devis introuvable.', Response::HTTP_NOT_FOUND);
        }

        $payload = json_decode($request->getContent() ?: '[]', true);
        if (!is_array($payload)) {
            return ApiResponse::error('Payload invalide.', Response::HTTP_BAD_REQUEST);
        }

        if (!isset($payload['productId'])) {
            return ApiResponse::error('Produit manquant.', Response::HTTP_UNPROCESSABLE_ENTITY);
        }

        $productId = (int) $payload['productId'];
        $product = $this->productRepository->find($productId);
        if ($product === null || !$product->isPublished()) {
            return ApiResponse::error('Produit introuvable.', Response::HTTP_NOT_FOUND);
        }

        $name = (string) ($payload['name'] ?? $product->getName());
        $unitPriceCents = isset($payload['unitPriceCents']) ? (int) $payload['unitPriceCents'] : $product->getPriceCents();

        $item = new QuoteItem($name, max(0, $unitPriceCents));
        $item->setItemType(QuoteItem::TYPE_PRODUCT)
            ->setProductId($product->getId())
            ->setDescription(self::strOrNull($payload['description'] ?? null))
            ->setUnit(self::strOrNull($payload['unit'] ?? (strtolower($product->getSellingType()) === 'rental' ? 'jour' : null)))
            ->setQuantity((int) ($payload['quantity'] ?? 1));

        if (isset($payload['vatRate'])) {
            $item->setVatRateBps((int) round(((float) $payload['vatRate']) * 100));
        } elseif (isset($payload['vatRateBps'])) {
            $item->setVatRateBps((int) $payload['vatRateBps']);
        }

        if (isset($payload['discountCents'])) {
            $item->setDiscountCents((int) $payload['discountCents']);
        }

        $quote->addItem($item);
        $this->em->persist($item);
        $this->em->flush();

        return ApiResponse::success(QuoteFormatter::formatQuote($quote, $this->calculator));
    }

    private static function strOrNull(mixed $v): ?string
    {
        if ($v === null) { return null; }
        $s = trim((string) $v);
        return $s === '' ? null : $s;
    }
}

