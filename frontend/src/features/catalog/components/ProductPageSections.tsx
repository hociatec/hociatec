import { Link } from 'react-router-dom';

import { ProductCartActions } from '@/features/cart/components/ProductCartActions';
import { formatOptionalFrenchDate } from '@/shared/lib/formatters';
import type { CatalogProduct, ProductPublicReview } from '../api';
import { ProductMetaBadges } from '../components/ProductMetaBadges';
import { RatingStars } from '../components/RatingStars';
import { formatProductPrice } from '../utils/productPageDisplay';

type ProductSlide = CatalogProduct['gallery'][number];

export interface ProductVariantOption {
  id: number;
  slug: string;
  title: string;
  subtitle: string;
  storage: string | null;
  color: string | null;
  priceLabel: string;
  stockLabel: string;
  isAvailable: boolean;
}

export interface ProductVariantGroup {
  storage: string;
  items: ProductVariantOption[];
}

interface ProductGalleryProps {
  activeSlide: number;
  onImageError: (url: string) => void;
  onNextSlide: () => void;
  onPrevSlide: () => void;
  onSelectSlide: (index: number) => void;
  slides: ProductSlide[];
}

export const ProductGallery = ({
  activeSlide,
  onImageError,
  onNextSlide,
  onPrevSlide,
  onSelectSlide,
  slides,
}: ProductGalleryProps) => (
  <div className="catalog-detail-hero">
    {slides.length > 0 ? (
      <div className="catalog-slider">
        <div className="catalog-slider__viewport">
          {slides.map((slide, index) => (
            <img
              key={slide.url + index}
              src={slide.url}
              alt={slide.alt}
              className={`catalog-slider__image${index === activeSlide ? ' is-active' : ''}`}
              onError={() => onImageError(slide.url)}
            />
          ))}
        </div>
        {slides.length > 1 && (
          <>
            <button
              type="button"
              className="catalog-slider__control catalog-slider__control--prev"
              onClick={onPrevSlide}
              aria-label="Image precedente"
            >
              ‹
            </button>
            <button
              type="button"
              className="catalog-slider__control catalog-slider__control--next"
              onClick={onNextSlide}
              aria-label="Image suivante"
            >
              ›
            </button>
            <div className="catalog-slider__dots" role="tablist">
              {slides.map((slide, index) => (
                <button
                  key={slide.url + index}
                  type="button"
                  className={`catalog-slider__dot${index === activeSlide ? ' is-active' : ''}`}
                  onClick={() => onSelectSlide(index)}
                  aria-label={`Afficher l'image ${index + 1}`}
                  aria-pressed={index === activeSlide}
                />
              ))}
            </div>
          </>
        )}
      </div>
    ) : (
      <div className="catalog-product-card__placeholder catalog-detail-hero__placeholder">
        Produit
      </div>
    )}
  </div>
);

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
    <span className="catalog-badge">{product.category.name}</span>
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

interface ProductVariantPickerProps {
  currentProductId: number;
  groups: ProductVariantGroup[];
  onVariantChange: (variantId: string) => void;
}

const ProductVariantPicker = ({
  currentProductId,
  groups,
  onVariantChange,
}: ProductVariantPickerProps) => (
  <div className="catalog-detail-variant-picker">
    <strong>Choisir une variante</strong>
    <div className="catalog-detail-variant-groups" aria-label="Variantes du produit">
      {groups.map((group) => (
        <section key={group.storage} className="catalog-detail-variant-group">
          <h3 className="catalog-detail-variant-group__title">{group.storage}</h3>
          <div className="catalog-detail-variant-picker__grid" role="list">
            {group.items.map((variant) => (
              <button
                key={variant.id}
                type="button"
                className={`catalog-detail-variant-card${variant.id === currentProductId ? ' is-active' : ''}`}
                onClick={() => onVariantChange(String(variant.id))}
                aria-pressed={variant.id === currentProductId}
              >
                <span className="catalog-detail-variant-card__title">
                  {variant.title}
                </span>
                <span className="catalog-detail-variant-card__meta">
                  {variant.subtitle}
                </span>
                <span className="catalog-detail-variant-card__footer">
                  <span className="catalog-detail-variant-card__price">
                    {variant.priceLabel}
                  </span>
                  <span className="catalog-detail-variant-card__stock">
                    {variant.stockLabel}
                  </span>
                </span>
              </button>
            ))}
          </div>
        </section>
      ))}
    </div>
  </div>
);

interface ProductInfoHighlightProps {
  product: CatalogProduct;
  productDates: { created: string | null; updated: string | null } | null;
}

export const ProductInfoHighlight = ({
  product,
  productDates,
}: ProductInfoHighlightProps) => (
  <section className="catalog-detail-highlight">
    <div className="catalog-highlight-card">
      <h2>Informations clés</h2>
      <dl>
        <div>
          <dt>Prix public</dt>
          <dd>
            {formatProductPrice(product.priceCents)}{product.sellingType === 'rental' ? ' / mois' : ''}
          </dd>
        </div>
        <div>
          <dt>Référence</dt>
          <dd>
            {product.sku}
            <ProductMetaBadges
              sellingType={product.sellingType}
              categoryName={product.category.name}
              variant="detail"
            />
          </dd>
        </div>
        <div>
          <dt>Marque</dt>
          <dd>{product.brand ?? '-'}</dd>
        </div>
        <div>
          <dt>Couleur</dt>
          <dd>{product.color ?? 'Par défaut'}</dd>
        </div>
        <div>
          <dt>Stockage</dt>
          <dd>{product.storageCapacity ?? '-'}</dd>
        </div>
        <div>
          <dt>Mémoire RAM</dt>
          <dd>{product.memoryRam ?? '-'}</dd>
        </div>
        <div>
          <dt>Année du modèle</dt>
          <dd>{product.releaseYear ?? '-'}</dd>
        </div>
        <div>
          <dt>Disponibilité</dt>
          <dd>
            {product.stock > 0
              ? `${product.stock} exemplaire${product.stock > 1 ? 's' : ''} en stock`
              : 'Sur commande'}
          </dd>
        </div>
        <div>
          <dt>Mise à jour</dt>
          <dd>{productDates?.updated ?? '-'}</dd>
        </div>
        <div>
          <dt>Création</dt>
          <dd>{productDates?.created ?? '-'}</dd>
        </div>
        <div>
          <dt>Catégorie</dt>
          <dd>{product.category.name}</dd>
        </div>
        <div>
          <dt>Visibilité</dt>
          <dd>{product.isPublished ? 'Publié' : 'Non publié'}</dd>
        </div>
        <div>
          <dt>Mise en avant</dt>
          <dd>{product.isFeaturedHome ? 'Présent sur l’accueil' : 'Classique'}</dd>
        </div>
      </dl>
    </div>
  </section>
);

interface ProductReviewsSectionProps {
  hasMoreReviews: boolean;
  onLoadMoreReviews: () => void;
  reviews: ProductPublicReview[];
  reviewsError: string | null;
  reviewsLoading: boolean;
  summaryAverage: number;
  summaryCount: number;
}

export const ProductReviewsSection = ({
  hasMoreReviews,
  onLoadMoreReviews,
  reviews,
  reviewsError,
  reviewsLoading,
  summaryAverage,
  summaryCount,
}: ProductReviewsSectionProps) => (
  <section className="catalog-reviews-section">
    <div className="catalog-reviews-card">
      <div className="catalog-reviews-card__header">
        <div>
          <h2>Avis clients</h2>
          <p className="muted">
            Ce que disent les clients ayant commandé ce produit.
          </p>
        </div>
        <div className="catalog-review-badge catalog-review-badge--summary">
          <RatingStars value={summaryAverage} />
          <div>
            <strong>{summaryAverage.toFixed(1)} / 5</strong>
            <span className="muted">
              {summaryCount} avis{summaryCount > 1 ? 's' : ''}
            </span>
          </div>
        </div>
      </div>
      {reviewsLoading && <p className="muted">Chargement des avis...</p>}
      {reviewsError && <p className="muted">{reviewsError}</p>}
      {!reviewsLoading && reviews.length === 0 && (
        <p className="muted">Pas encore d'avis pour ce produit.</p>
      )}
      <ul className="catalog-reviews-list">
        {reviews.map((review) => (
          <li key={review.id} className="catalog-review">
            <div className="catalog-review__header">
              <RatingStars value={review.score} />
              <div>
                <strong>{review.author.displayName}</strong>
                <span className="muted">
                  {formatOptionalFrenchDate(review.createdAt)}
                </span>
              </div>
            </div>
            {review.comment && <p>{review.comment}</p>}
          </li>
        ))}
      </ul>
      {hasMoreReviews && (
        <button
          type="button"
          className="catalog-review__load-more"
          onClick={onLoadMoreReviews}
          disabled={reviewsLoading}
        >
          Charger plus d'avis
        </button>
      )}
    </div>
  </section>
);
