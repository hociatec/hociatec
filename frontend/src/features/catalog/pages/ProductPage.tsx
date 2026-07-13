import { useCallback, useEffect, useMemo, useState } from 'react';
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
import { addFavorite, fetchFavorites, removeFavorite } from '@/features/favorites/api';

import './CatalogPages.css';

const formatPrice = (priceCents: number) =>
  new Intl.NumberFormat('fr-FR', {
    style: 'currency',
    currency: 'EUR',
  }).format(priceCents / 100);

const formatDate = (value: string) => {
  const date = new Date(value);

  if (Number.isNaN(date.getTime())) {
    return null;
  }

  return date.toLocaleDateString('fr-FR', {
    day: '2-digit',
    month: 'long',
    year: 'numeric',
  });
};

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
  const reviewsPerPage = 5;
  const { status: authStatus } = useAuth();
  const { show: showToast } = useToast();
  const [favoriteStatus, setFavoriteStatus] = useState<'idle' | 'loading' | 'ready' | 'error'>('idle');
  const [isFavorite, setIsFavorite] = useState(false);
  const [favoriteAction, setFavoriteAction] = useState<'idle' | 'saving'>('idle');
  const isAuthenticated = authStatus === 'authenticated';
  const [selectedColor, setSelectedColor] = useState<string | null>(null);
  const [selectedStorage, setSelectedStorage] = useState<string | null>(null);

  const extractBaseProductName = useCallback((value: string) => value.replace(/\s*\([^)]*\)\s*$/u, '').replace(/\s*\([^)]*\)\s*$/u, '').trim(), []);

  const canonicalUrl = slug ? `${SITE_URL}/catalogue/produits/${slug}` : undefined;
  const productStructuredData = product
    ? {
        '@context': 'https://schema.org',
        '@type': 'Product',
        name: product.name,
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

  useDocumentTitle(product ? `${product.name} - Catalogue` : 'Produit - Catalogue');
  useMetaTags({
    title: product ? `${product.name} — Catalogue` : 'Produit - Catalogue',
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
    setLoading(true);
    setError(null);

    void fetchPublicProduct(slug)
      .then((result) => setProduct(result))
      .catch((err: Error) => setError(err.message || 'Produit introuvable.'))
      .finally(() => setLoading(false));
  }, [slug]);

  useEffect(() => {
    setActiveSlide(0);
  }, [product?.id]);

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
      setSelectedColor(null);
      setSelectedStorage(null);
      return;
    }

    const variantGroup = product.variantGroup?.trim() || extractBaseProductName(product.name);

    void fetchPublicProducts({
      category: product.category.slug,
      sellingType: product.sellingType,
      sort: 'release_year_desc',
    })
      .then((items) => {
        const variants = items.filter(
          (item) => (item.variantGroup?.trim() || extractBaseProductName(item.name)) === variantGroup,
        );
        setColorVariants(variants.length > 0 ? variants : [product]);
      })
      .catch(() => setColorVariants([product]));
  }, [
    product,
    extractBaseProductName,
  ]);

  useEffect(() => {
    if (!product) {
      return;
    }

    setSelectedColor(product.color ?? null);
    setSelectedStorage(product.storageCapacity ?? null);
  }, [product?.id]);

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

  useEffect(() => {
    if (!product || product.gallery.length <= 1) {
      return;
    }

    const interval = window.setInterval(() => {
      setActiveSlide((previous) => (previous + 1) % product.gallery.length);
    }, 5000);

    return () => window.clearInterval(interval);
  }, [product]);

  const slides =
    product && product.gallery.length > 0
      ? product.gallery
      : product && product.imageUrl
        ? [
            {
              position: 0,
              url: product.imageUrl,
              alt: product.imageAlt ?? product.name,
              isPrimary: true,
            },
          ]
        : [];

  const productDates = useMemo(() => {
    if (!product) return null;

    return {
      created: formatDate(product.createdAt),
      updated: formatDate(product.updatedAt),
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
  const availableColors = useMemo(() => {
    const map = new Map<string, { product: CatalogProduct; stock: number }>();

    for (const item of colorVariants) {
      const key = item.color?.trim() || 'default';
      const existing = map.get(key);
      if (!existing) {
        map.set(key, { product: item, stock: item.stock });
      } else {
        existing.stock += item.stock;
      }
    }

    return Array.from(map.entries()).map(([key, item]) => ({
      key,
      label: item.product.color ?? 'Couleur par défaut',
      product: item.product,
      stock: item.stock,
      active: item.product.id === product?.id,
    }));
  }, [colorVariants, product?.id]);

  const availableStorages = useMemo(() => {
    const map = new Map<string, { product: CatalogProduct; stock: number }>();

    for (const item of colorVariants) {
      const key = item.storageCapacity?.trim() || 'default';
      const existing = map.get(key);
      if (!existing) {
        map.set(key, { product: item, stock: item.stock });
      } else {
        existing.stock += item.stock;
      }
    }

    return Array.from(map.entries()).map(([key, item]) => ({
      key,
      label: item.product.storageCapacity ?? 'Stockage par défaut',
      product: item.product,
      stock: item.stock,
      active: item.product.id === product?.id,
    }));
  }, [colorVariants, product?.id]);

  const findVariant = useCallback(
    (color?: string | null, storageCapacity?: string | null) => {
      const matches = colorVariants.find((variant) => {
        const variantColor = variant.color?.trim() || null;
        const variantStorage = variant.storageCapacity?.trim() || null;
        return (
          (color === undefined || color === null || variantColor === color) &&
          (storageCapacity === undefined || storageCapacity === null || variantStorage === storageCapacity)
        );
      });

      if (matches) {
        return matches;
      }

      if (color !== undefined && color !== null) {
        return colorVariants.find((variant) => (variant.color?.trim() || null) === color) ?? null;
      }

      if (storageCapacity !== undefined && storageCapacity !== null) {
        return (
          colorVariants.find((variant) => (variant.storageCapacity?.trim() || null) === storageCapacity) ??
          null
        );
      }

      return null;
    },
    [colorVariants],
  );

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
    if (!product || slides.length <= 1) return;
    setActiveSlide((previous) => (previous + 1) % slides.length);
  };

  const handlePrevSlide = () => {
    if (!product || slides.length <= 1) return;
    setActiveSlide((previous) => (previous - 1 + slides.length) % slides.length);
  };

  const handleLoadMoreReviews = () => {
    loadReviews(reviewsPage + 1, true);
  };

  const RatingStars = ({ value, compact = false }: { value: number; compact?: boolean }) => (
    <div
      className={`catalog-review-stars${compact ? ' catalog-review-stars--compact' : ''}`}
      aria-label={`${value.toFixed(1)} sur 5`}
    >
      {[1, 2, 3, 4, 5].map((index) => (
        <span key={index} className={index <= Math.round(value) ? 'is-active' : ''}>
          ★
        </span>
      ))}
    </div>
  );

  return (
    <SiteLayout headerVariant="light">
      <div className="catalog-detail-layout">
        <Link
          to={product ? `/catalogue/${product.category.slug}` : '/'}
          className="catalog-page__breadcrumbs"
        >
          Retour
        </Link>

        {loading && <p>Chargement du produit...</p>}
        {error && <div className="register-form__alert">{error}</div>}

        {!loading && !error && product && (
          <>
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
                      />
                    ))}
                  </div>
                  {slides.length > 1 && (
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
                        {slides.map((slide, index) => (
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
                <div className="catalog-product-card__placeholder" style={{ height: 320 }}>
                  {product.name.charAt(0).toUpperCase()}
                </div>
              )}
            </div>

            <header className="catalog-detail-header">
              <span className="catalog-badge">{product.category.name}</span>
              <h1>{product.name}</h1>
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
                          : 'border-slate-300 text-slate-700 hover:border-slate-500'
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
                    className="text-sm font-semibold text-slate-600 underline hover:text-slate-800"
                  >
                    Connectez-vous pour ajouter aux favoris
                  </Link>
                )}
              </div>
              {availableColors.length > 0 && (
                <div className="catalog-detail-variant-picker">
                  <strong>Couleur</strong>
                  <div className="catalog-detail-variant-picker__options">
                    {availableColors.map((variant) => (
                      <button
                        key={variant.product.id}
                        type="button"
                        className={`catalog-detail-variant-picker__option${variant.active ? ' is-active' : ''}`}
                        onClick={() => {
                          if (!variant.active) {
                            const target = findVariant(variant.label === 'Couleur par défaut' ? null : variant.label, selectedStorage);
                            navigate(`/catalogue/produits/${target?.slug ?? variant.product.slug}`);
                          }
                        }}
                        disabled={variant.stock <= 0}
                        title={
                          variant.stock <= 0
                            ? `${variant.label} indisponible`
                            : `${variant.label} · ${variant.stock} en stock`
                        }
                        aria-pressed={variant.active}
                        aria-label={`${variant.label}${variant.stock > 0 ? `, ${variant.stock} en stock` : ', indisponible'}`}
                      >
                        {variant.label}
                        <span className="catalog-detail-variant-picker__stock">
                          {variant.stock > 0 ? `${variant.stock}` : '0'}
                        </span>
                      </button>
                    ))}
                  </div>
                </div>
              )}
              {availableStorages.length > 0 && (
                <div className="catalog-detail-variant-picker">
                  <strong>Stockage</strong>
                  <div className="catalog-detail-variant-picker__options">
                    {availableStorages.map((variant) => (
                      <button
                        key={variant.product.id}
                        type="button"
                        className={`catalog-detail-variant-picker__option${variant.active ? ' is-active' : ''}`}
                        onClick={() => {
                          const target = findVariant(selectedColor, variant.label === 'Stockage par défaut' ? null : variant.label);
                          navigate(`/catalogue/produits/${target?.slug ?? variant.product.slug}`);
                        }}
                        disabled={variant.stock <= 0}
                        title={
                          variant.stock <= 0
                            ? `${variant.label} indisponible`
                            : `${variant.label} · ${variant.stock} en stock`
                        }
                        aria-pressed={variant.active}
                        aria-label={`${variant.label}${variant.stock > 0 ? `, ${variant.stock} en stock` : ', indisponible'}`}
                      >
                        {variant.label}
                        <span className="catalog-detail-variant-picker__stock">
                          {variant.stock > 0 ? `${variant.stock}` : '0'}
                        </span>
                      </button>
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
                      {formatPrice(product.priceCents)}{product.sellingType === 'rental' ? ' / mois' : ''}
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
                {reviewsLoading && <p className="muted">Chargement des avis...</p>}
                {reviewsError && <div className="register-form__alert">{reviewsError}</div>}
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
                            {new Date(review.createdAt).toLocaleDateString('fr-FR')}
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
