import { useCallback, useEffect, useMemo, useRef, useState } from 'react';
import { Link, useNavigate, useParams } from 'react-router-dom';

import { fetchProductReviews, fetchPublicProduct, fetchPublicProducts, type CatalogProduct, type ProductPublicReview } from '../api';
import { ProductMetaBadges } from '../components/ProductMetaBadges';
import { SiteLayout } from '../../../shared/components/SiteLayout';
import { useDocumentTitle } from '../../../shared/hooks/useDocumentTitle';
import { useMetaTags } from '@/shared/hooks/useMetaTags';
import { ProductCartActions } from '@/features/cart/components/ProductCartActions';
import { SITE_URL } from '@/shared/config/seoConfig';
import { useAuth } from '@/features/auth/hooks/useAuth';
import { useToast } from '@/shared/components/ui/toast';
import { FeedbackMessage, LoadingState } from '@/shared/components/ui/page-state';
import { addFavorite, fetchFavorites, removeFavorite } from '@/features/favorites/api';
import { getCatalogProductDisplayName } from '../utils/productDisplay';
import { buildVariantGroupKey, formatProductDate, formatProductPrice } from '../utils/productPageDisplay';
import { RatingStars } from '../components/RatingStars';
import { formatOptionalFrenchDate } from '@/shared/lib/formatters';

import './CatalogPages.css';

export const ProductPage = () => {
  const { slug } = useParams<{ slug: string }>();
  const navigate = useNavigate();
  const [product, setProduct] = useState<CatalogProduct | null>(null);
  const [colorVariants, setColorVariants] = useState<CatalogProduct[]>([]);
  const [loading, setLoading] = useState(true);
  const [error, setError] = useState<string | null>(null);
  const [activeSlide, setActiveSlide] = useState(0);
  const [reviews, setReviews] = useState<ProductPublicReview[]>([]);
  const [reviewsMeta, setReviewsMeta] = useState<{ total: number; average: number }>({ total: 0, average: 0 });
  const [reviewsLoading, setReviewsLoading] = useState(false);
  const [reviewsError, setReviewsError] = useState<string | null>(null);
  const [reviewsPage, setReviewsPage] = useState(1);
  const [hasMoreReviews, setHasMoreReviews] = useState(false);
  const [failedSlideUrls, setFailedSlideUrls] = useState<Set<string>>(() => new Set());
  const reviewsPerPage = 5;
  const { status: authStatus } = useAuth();
  const { show: showToast } = useToast();
  const [favoriteStatus, setFavoriteStatus] = useState<'idle' | 'loading' | 'ready' | 'error'>('idle');
  const [isFavorite, setIsFavorite] = useState(false);
  const [favoriteAction, setFavoriteAction] = useState<'idle' | 'saving'>('idle');
  const isAuthenticated = authStatus === 'authenticated';
  const previousSlidesSignatureRef = useRef<string>('');
  const canonicalUrl = product ? `${SITE_URL}/catalogue/produits/${product.slug}` : slug ? `${SITE_URL}/catalogue/produits/${slug}` : undefined;
  const productDisplayName = product ? getCatalogProductDisplayName(product) : null;
  const productStructuredData = product
    ? {
        '@context': 'https://schema.org',
        '@type': 'Product',
        name: productDisplayName,
        description:
          product.shortDescription ?? 'Une solution personnalisée pour vos besoins numériques.',
        sku: product.sku,
        url: canonicalUrl,
        category: product.category.name,
        image: product.imageUrl ?? undefined,
        offers: {
          '@type': 'Offer',
          priceCurrency: 'EUR',
          price: (product.priceCents / 100).toFixed(2),
          availability: 'https://schema.org/InStock',
        },
      }
    : undefined;

  useDocumentTitle(productDisplayName ? `${productDisplayName} - Catalogue` : 'Produit - Catalogue');
  useMetaTags({
    title: productDisplayName ? `${productDisplayName} — Catalogue` : 'Produit - Catalogue',
    description: product?.shortDescription ?? 'Une solution personnalisée pour vos besoins numériques.',
    imageUrl: product?.imageUrl ?? undefined,
    type: 'product',
    canonicalUrl,
    structuredData: productStructuredData,
  });

  const loadReviews = useCallback(
    (page = 1, append = false) => {
      if (!product) return;
      setReviewsLoading(true);
      setReviewsError(null);

      void fetchProductReviews(product.slug, { page, perPage: reviewsPerPage })
        .then((response) => {
          const meta = response?.meta ?? { total: 0, average: 0 };
          setReviewsMeta({ total: meta.total, average: meta.average });
          let nextLength = 0;
          setReviews((prev) => {
            const incoming = response?.items ?? [];
            const next = append ? [...prev, ...incoming] : incoming;
            nextLength = next.length;
            return next;
          });
          setHasMoreReviews(meta.total > nextLength);
          setReviewsPage(page);
        })
        .catch((err: Error) => setReviewsError(err.message || 'Impossible de charger les avis.'))
        .finally(() => setReviewsLoading(false));
    },
    [product, reviewsPerPage],
  );

  useEffect(() => {
    if (!slug) return;

    if (product?.slug === slug) {
      setLoading(false);
      setError(null);
      return;
    }

    setLoading(true);
    setError(null);

    void fetchPublicProduct(slug)
      .then((result) => {
        setProduct(result);
      })
      .catch((err: Error) => setError(err.message || 'Produit introuvable.'))
      .finally(() => {
        setLoading(false);
      });
  }, [slug, product?.slug]);

  useEffect(() => {
    if (!product) return;
    setReviews([]);
    setReviewsMeta({
      total: product.reviews?.count ?? 0,
      average: product.reviews?.average ?? 0,
    });
    setHasMoreReviews(false);
    setReviewsPage(1);
    setReviewsError(null);
    loadReviews(1, false);
  }, [product?.slug, loadReviews]);

  useEffect(() => {
    if (!product) {
      setColorVariants([]);
      return;
    }

    const variantGroup = buildVariantGroupKey(product);

    void fetchPublicProducts({
      category: product.category.slug,
      sellingType: product.sellingType,
      sort: 'release_year_desc',
      perPage: 100,
    })
      .then((items) => {
        const variants = items.filter(
          (item) => buildVariantGroupKey(item) === variantGroup,
        );
        setColorVariants(variants.length > 0 ? variants : [product]);
      })
      .catch(() => setColorVariants([product]));
  }, [product]);


  useEffect(() => {
    if (!product?.id || !isAuthenticated) {
      setIsFavorite(false);
      setFavoriteStatus('idle');
      return;
    }

    let cancelled = false;
    setFavoriteStatus('loading');

    void fetchFavorites()
      .then((items) => {
        if (cancelled) {
          return;
        }
        setIsFavorite(items.some((item) => item.product.id === product.id));
        setFavoriteStatus('ready');
      })
      .catch(() => {
        if (cancelled) {
          return;
        }
        setFavoriteStatus('error');
      });

    return () => {
      cancelled = true;
    };
  }, [isAuthenticated, product?.id]);

  const slides = useMemo(
    () =>
      product && product.gallery.length > 0
        ? product.gallery
        : product && product.imageUrl
          ? [
              {
                position: 0,
                url: product.imageUrl,
                alt: product.imageAlt ?? productDisplayName ?? product.name,
                isPrimary: true,
              },
            ]
          : [],
    [product],
  );
  const visibleSlides = useMemo(
    () => slides.filter((slide) => !failedSlideUrls.has(slide.url)),
    [failedSlideUrls, slides],
  );

  useEffect(() => {
    const signature = visibleSlides.map((slide) => slide.url).join('|');
    const previousSignature = previousSlidesSignatureRef.current;

    if (signature === previousSignature) {
      setActiveSlide((previous) => (visibleSlides.length === 0 ? 0 : Math.min(previous, visibleSlides.length - 1)));
      return;
    }

    previousSlidesSignatureRef.current = signature;
    setActiveSlide(0);
  }, [visibleSlides]);

  useEffect(() => {
    setFailedSlideUrls(new Set());
  }, [product?.slug]);

  const productDates = useMemo(() => {
    if (!product) return null;

    return {
      created: formatProductDate(product.createdAt),
      updated: formatProductDate(product.updatedAt),
    };
  }, [product]);

  const summaryAverage = reviewsMeta.total > 0 ? reviewsMeta.average : product?.reviews?.average ?? 0;
  const summaryCount = reviewsMeta.total > 0 ? reviewsMeta.total : product?.reviews?.count ?? 0;
  const favoriteButtonLabel =
    favoriteAction === 'saving'
      ? 'Veuillez patienter...'
      : isFavorite
        ? 'Retirer des favoris'
        : 'Ajouter aux favoris';
  const favoriteButtonDisabled = favoriteAction === 'saving' || favoriteStatus === 'loading';
  const variantOptions = useMemo(
    () =>
      [...colorVariants]
        .sort((left, right) => {
          const leftPosition = left.variantPosition ?? Number.MAX_SAFE_INTEGER;
          const rightPosition = right.variantPosition ?? Number.MAX_SAFE_INTEGER;

          if (leftPosition !== rightPosition) {
            return leftPosition - rightPosition;
          }

          return left.id - right.id;
        })
        .map((variant) => {
          const storage = variant.storageCapacity?.trim() || null;
          const color = variant.color?.trim() || null;
          const attributes = [storage, color].filter((value): value is string => Boolean(value));
          const title = color ?? storage ?? variant.name;
          const subtitle =
            storage && color
              ? `${storage} • ${color}`
              : attributes.length > 0
                ? attributes.join(' • ')
                : 'Version disponible';

          return {
            id: variant.id,
            slug: variant.slug,
            title,
            subtitle,
            storage,
            color,
            priceLabel: `${formatProductPrice(variant.priceCents)}${variant.sellingType === 'rental' ? ' / mois' : ''}`,
            stockLabel:
              variant.stock > 0
                ? `${variant.stock} en stock`
                : 'Indisponible',
            isAvailable: variant.stock > 0,
          };
        }),
    [colorVariants],
  );
  const variantGroups = useMemo(() => {
    const groups = new Map<string, typeof variantOptions>();

    variantOptions.forEach((variant) => {
      const key = variant.storage ?? 'Autres versions';
      const items = groups.get(key) ?? [];
      items.push(variant);
      groups.set(key, items);
    });

    return Array.from(groups.entries()).map(([storage, items]) => ({
      storage,
      items: items.sort((left, right) => left.title.localeCompare(right.title, 'fr')),
    }));
  }, [variantOptions]);

  const handleVariantChange = (variantId: string) => {
    const target = colorVariants.find((variant) => variant.id === Number(variantId));

    if (!target || target.id === product?.id) {
      return;
    }

    setLoading(true);
    setError(null);

    void navigate(`/catalogue/produits/${target.slug}`);
  };

  const handleAddFavorite = () => {
    if (!product) {
      return;
    }
    setFavoriteAction('saving');
    void addFavorite(product.id)
      .then(({ alreadyFavorite }) => {
        setIsFavorite(true);
        showToast(
          alreadyFavorite
            ? 'Ce produit est déjà présent dans vos favoris.'
            : 'Produit ajouté à vos favoris.',
        );
      })
      .catch((error: unknown) => {
        const message =
          error instanceof Error
            ? error.message
            : "Impossible d'ajouter ce produit aux favoris.";
        showToast(message, { variant: 'error' });
      })
      .finally(() => setFavoriteAction('idle'));
  };

  const handleRemoveFavorite = () => {
    if (!product) {
      return;
    }
    setFavoriteAction('saving');
    void removeFavorite(product.id)
      .then(() => {
        setIsFavorite(false);
        showToast('Produit retiré de vos favoris.');
      })
      .catch((error: unknown) => {
        const message =
          error instanceof Error
            ? error.message
            : 'Impossible de retirer ce produit des favoris.';
        showToast(message, { variant: 'error' });
      })
      .finally(() => setFavoriteAction('idle'));
  };

  const handleNextSlide = () => {
    if (!product || visibleSlides.length <= 1) return;
    setActiveSlide((previous) => (previous + 1) % visibleSlides.length);
  };

  const handlePrevSlide = () => {
    if (!product || visibleSlides.length <= 1) return;
    setActiveSlide((previous) => (previous - 1 + visibleSlides.length) % visibleSlides.length);
  };

  const handleLoadMoreReviews = () => {
    loadReviews(reviewsPage + 1, true);
  };

  return (
    <SiteLayout headerVariant="light">
      <div className="catalog-detail-layout">
        <Link
          to={product ? `/catalogue/${product.category.slug}` : '/'}
          className="catalog-page__breadcrumbs"
        >
          Retour
        </Link>

        {loading && <LoadingState>Chargement du produit...</LoadingState>}
        {error && <FeedbackMessage>{error}</FeedbackMessage>}

        {!loading && !error && product && (
          <>
            <div className="catalog-detail-hero">
              {visibleSlides.length > 0 ? (
                <div className="catalog-slider">
                  <div className="catalog-slider__viewport">
                    {visibleSlides.map((slide, index) => (
                      <img
                        key={slide.url + index}
                        src={slide.url}
                        alt={slide.alt}
                        className={`catalog-slider__image${index === activeSlide ? ' is-active' : ''}`}
                        onError={() =>
                          setFailedSlideUrls((previous) => new Set(previous).add(slide.url))
                        }
                      />
                    ))}
                  </div>
                  {visibleSlides.length > 1 && (
                    <>
                      <button
                        type="button"
                        className="catalog-slider__control catalog-slider__control--prev"
                        onClick={handlePrevSlide}
                        aria-label="Image precedente"
                      >
                        ‹
                      </button>
                      <button
                        type="button"
                        className="catalog-slider__control catalog-slider__control--next"
                        onClick={handleNextSlide}
                        aria-label="Image suivante"
                      >
                        ›
                      </button>
                      <div className="catalog-slider__dots" role="tablist">
                        {visibleSlides.map((slide, index) => (
                          <button
                            key={slide.url + index}
                            type="button"
                            className={`catalog-slider__dot${index === activeSlide ? ' is-active' : ''}`}
                            onClick={() => setActiveSlide(index)}
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
                    {summaryAverage.toFixed(1)} / 5 · {summaryCount}{' '}
                    avis
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
                      onClick={isFavorite ? handleRemoveFavorite : handleAddFavorite}
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
                <div className="catalog-detail-variant-picker">
                  <strong>Choisir une variante</strong>
                  <div className="catalog-detail-variant-groups" aria-label="Variantes du produit">
                    {variantGroups.map((group) => (
                      <section key={group.storage} className="catalog-detail-variant-group">
                        <h3 className="catalog-detail-variant-group__title">{group.storage}</h3>
                        <div className="catalog-detail-variant-picker__grid" role="list">
                          {group.items.map((variant) => (
                            <button
                              key={variant.id}
                              type="button"
                              className={`catalog-detail-variant-card${variant.id === product.id ? ' is-active' : ''}`}
                              onClick={() => handleVariantChange(String(variant.id))}
                              aria-pressed={variant.id === product.id}
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
              )}
            </header>

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

            <section className="catalog-detail-content">
              <h2>Description du produit</h2>
              <p>{product.description}</p>
            </section>

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
                {reviewsLoading && <LoadingState>Chargement des avis...</LoadingState>}
                {reviewsError && <FeedbackMessage>{reviewsError}</FeedbackMessage>}
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
                    onClick={handleLoadMoreReviews}
                    disabled={reviewsLoading}
                  >
                    Charger plus d'avis
                  </button>
                )}
              </div>
            </section>
          </>
        )}
      </div>
    </SiteLayout>
  );
};
