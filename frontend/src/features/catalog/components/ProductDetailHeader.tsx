import { ProductCartActions } from '@/features/cart/publicApi';
import { RatingStars } from '@/features/catalog/components/RatingStars';
import { ProductVariantPicker } from '@/features/catalog/components/ProductVariantPicker';
import type { CatalogProduct } from '../api';
import type { ProductVariantGroup, ProductVariantOption } from './productVariantTypes';

interface ProductDetailHeaderProps {
  favoriteButtonDisabled: boolean;
  favoriteButtonLabel: string;
  favoriteStatus: 'idle' | 'loading' | 'ready' | 'error';
  isAuthenticated: boolean;
  isFavorite: boolean;
  onAddFavorite: () => void;
  onRemoveFavorite: () => void;
  onVariantChange: (variantId: string) => void;
  product: CatalogProduct;
  productDisplayName: string | null;
  summaryAverage: number;
  summaryCount: number;
  variantGroups: ProductVariantGroup[];
  variantOptions: ProductVariantOption[];
}

export const ProductDetailHeader = ({
  favoriteButtonDisabled,
  favoriteButtonLabel,
  favoriteStatus,
  isAuthenticated,
  isFavorite,
  onAddFavorite,
  onRemoveFavorite,
  onVariantChange,
  product,
  productDisplayName,
  summaryAverage,
  summaryCount,
  variantGroups,
  variantOptions,
}: ProductDetailHeaderProps) => {
  const favoriteButtonClassName = `inline-flex items-center rounded-full border px-5 py-2 text-sm font-semibold transition ${
    isAuthenticated
      ? isFavorite
        ? 'border-red-300 text-red-600 hover:border-red-400'
        : 'border-brand-200 text-stone-700 hover:border-brand-600'
      : 'cursor-not-allowed border-stone-200 text-stone-400 opacity-70'
  }`;

  return (
    <header className="catalog-detail-header">
      <h1>{productDisplayName}</h1>
      {summaryCount > 0 && (
        <div className="catalog-review-badge">
          <span className="sr-only">
            Note moyenne de {summaryAverage.toFixed(1)} sur 5, basée sur {summaryCount} avis.
          </span>
          <RatingStars value={summaryAverage} compact decorative />
          <span aria-hidden="true">
            {summaryAverage.toFixed(1)} / 5 · {summaryCount} avis
          </span>
        </div>
      )}
      <div className="catalog-detail-actions">
        <ProductCartActions product={product} variant="detail" />
        <div className="flex flex-col gap-1">
          <button
            type="button"
            className={favoriteButtonClassName}
            disabled={!isAuthenticated || favoriteButtonDisabled}
            onClick={isFavorite ? onRemoveFavorite : onAddFavorite}
          >
            {favoriteButtonLabel}
          </button>
          {favoriteStatus === 'error' && (
            <span className="text-xs text-red-600">
              Impossible de verifier vos favoris actuellement.
            </span>
          )}
        </div>
      </div>
      {variantOptions.length > 1 && (
        <ProductVariantPicker
          currentProductId={product.id}
          groups={variantGroups}
          onVariantChange={onVariantChange}
        />
      )}
    </header>
  );
};
