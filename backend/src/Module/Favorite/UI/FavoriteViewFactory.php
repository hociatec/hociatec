<?php

declare(strict_types=1);

namespace App\Module\Favorite\UI;

use App\Module\Catalog\Application\Port\ProductRepositoryPort;
use App\Module\Catalog\Application\Projection\CatalogFormatter;
use App\Module\Favorite\Domain\Entity\Favorite;
use App\Module\News\Application\Port\NewsArticleRepositoryPort;
use App\Module\News\Application\Projection\NewsFormatter;
use App\Module\Service\Application\Port\ServiceOfferingRepositoryPort;
use App\Module\Service\Application\Projection\ServiceFormatter;

final readonly class FavoriteViewFactory
{
    public function __construct(
        private ProductRepositoryPort $products,
        private CatalogFormatter $catalogFormatter,
        private ServiceOfferingRepositoryPort $services,
        private ServiceFormatter $serviceFormatter,
        private NewsArticleRepositoryPort $articles,
        private NewsFormatter $newsFormatter,
    ) {
    }

    /** @return array<string, mixed>|null */
    public function favorite(Favorite $favorite): ?array
    {
        $target = $this->target($favorite);
        if (null === $target) {
            return null;
        }

        return [
            'category' => $favorite->getCategory(),
            'targetId' => $favorite->getTargetId(),
            'addedAt' => $favorite->getCreatedAt()->format(DATE_ATOM),
            ...$target,
        ];
    }

    /** @return array<string, mixed>|null */
    private function target(Favorite $favorite): ?array
    {
        return match ($favorite->getCategory()) {
            Favorite::CATEGORY_PRODUCT => $this->product($favorite->getTargetId()),
            Favorite::CATEGORY_SERVICE => $this->service($favorite->getTargetId()),
            Favorite::CATEGORY_NEWS => $this->article($favorite->getTargetId()),
            Favorite::CATEGORY_PODCAST => ['podcast' => null],
            default => null,
        };
    }

    /** @return array{product: array<string, mixed>}|null */
    private function product(int $targetId): ?array
    {
        $product = $this->products->find($targetId);
        if (null === $product || false === $product->isPublished()) {
            return null;
        }

        return ['product' => $this->catalogFormatter->formatProduct($product)];
    }

    /** @return array{service: array<string, mixed>}|null */
    private function service(int $targetId): ?array
    {
        $service = $this->services->find($targetId);
        if (null === $service) {
            return null;
        }

        return ['service' => $this->serviceFormatter->format($service)];
    }

    /** @return array{article: array<string, mixed>}|null */
    private function article(int $targetId): ?array
    {
        $article = $this->articles->find($targetId);
        if (null === $article || false === $article->isPublished()) {
            return null;
        }

        $publishedAt = $article->getPublishedAt();
        if (null !== $publishedAt && $publishedAt > new \DateTimeImmutable()) {
            return null;
        }

        return ['article' => $this->newsFormatter->article($article)];
    }
}
