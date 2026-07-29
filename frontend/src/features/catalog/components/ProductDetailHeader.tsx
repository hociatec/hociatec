import { Link } from 'react-router';

import { ProductCartActions } from '@/features/cart/components/ProductCartActions';
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
}: ProductDetailHeaderProps) => (
  <header className="catalog-detail-header">
    <h1>{productDisplayName}</h1>
    <p className="catalog-detail-summary">
      {product.shortDescription ??
        'Une solution personnalisee pour accelerer vos projets numeriques.'}
    </p>
    {summaryCount > 0 && (
      <div className="catalog-review-badge">
        <RatingStars value={summaryAverage} compact />
        <span>
          {summaryAverage.toFixed(1)} / 5 · {summaryCount} avis
        </span>
      </div>
    )}
    <div className="catalog-detail-actions">
      <ProductCartActions product={product} variant="detail" />
      {isAuthenticated ? (
        <div className="flex flex-col gap-1">
          <button
            type="button"
            className={`inline-flex items-center rounded-full border px-5 py-2 text-sm font-semibold transition ${
              isFavorite
                ? 'border-red-300 text-red-600 hover:border-red-400'
                : 'border-brand-200 text-stone-700 hover:border-brand-600'
            }`}
            disabled={favoriteButtonDisabled}
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
      ) : (
        <Link
          to="/login"
          className="text-sm font-semibold text-stone-600 underline hover:text-stone-800"
        >
          Connectez-vous pour ajouter aux favoris
        </Link>
      )}
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
