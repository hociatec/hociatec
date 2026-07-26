import { useEffect, useMemo, useRef, useState } from 'react';
import { Link, useNavigate, useParams } from 'react-router-dom';

import { SiteLayout } from '../../../shared/components/SiteLayout';
import { useDocumentTitle } from '../../../shared/hooks/useDocumentTitle';
import { useMetaTags } from '@/shared/hooks/useMetaTags';
import { SITE_URL } from '@/shared/config/seoConfig';
import { useToast } from '@/shared/components/ui/toast';
import { FeedbackMessage, LoadingState } from '@/shared/components/ui/page-state';
import { useProductFavorite } from '@/features/catalog/hooks/useProductFavorite';
import { getCatalogProductDisplayName } from '../utils/productDisplay';
import {
  buildProductSlides,
  buildProductVariantOptions,
  formatProductDate,
  groupProductVariants,
} from '../utils/productPageDisplay';
import {
  ProductDetailHeader,
  ProductGallery,
  ProductInfoHighlight,
  ProductReviewsSection,
} from '@/features/catalog/components/ProductPageSections';
import { ProductDescriptionSection } from '@/features/catalog/components/ProductDescriptionSection';
import { useProductPageData } from '@/features/catalog/hooks/useProductPageData';
import { useProductReviews } from '@/features/catalog/hooks/useProductReviews';

import './CatalogPages.css';

export const ProductPage = () => {
  const { slug } = useParams<{ slug: string }>();
  const navigate = useNavigate();
  const { product, colorVariants, loading, error } = useProductPageData(slug);
  const [activeSlide, setActiveSlide] = useState(0);
  const { hasMoreReviews, loadMoreReviews, reviews, reviewsError, reviewsLoading, reviewsMeta } =
    useProductReviews(product);
  const [failedSlideUrls, setFailedSlideUrls] = useState<Set<string>>(() => new Set());
  const { show: showToast } = useToast();
  const {
    isAuthenticated,
    isFavorite,
    favoriteStatus,
    favoriteAction,
    toggle: toggleFavorite,
  } = useProductFavorite(product?.id);
  const previousSlidesSignatureRef = useRef<string>('');
  const canonicalUrl = product
    ? `${SITE_URL}/catalogue/produits/${product.slug}`
    : slug
      ? `${SITE_URL}/catalogue/produits/${slug}`
      : undefined;
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

  useDocumentTitle(
    productDisplayName ? `${productDisplayName} - Catalogue` : 'Produit - Catalogue',
  );
  useMetaTags({
    title: productDisplayName ? `${productDisplayName} — Catalogue` : 'Produit - Catalogue',
    description:
      product?.shortDescription ?? 'Une solution personnalisée pour vos besoins numériques.',
    imageUrl: product?.imageUrl ?? undefined,
    type: 'product',
    canonicalUrl,
    structuredData: productStructuredData,
  });

  const slides = useMemo(
    () => buildProductSlides(product, productDisplayName),
    [product, productDisplayName],
  );
  const visibleSlides = useMemo(
    () => slides.filter((slide) => !failedSlideUrls.has(slide.url)),
    [failedSlideUrls, slides],
  );

  useEffect(() => {
    const signature = visibleSlides.map((slide) => slide.url).join('|');
    const previousSignature = previousSlidesSignatureRef.current;

    if (signature === previousSignature) {
      setActiveSlide((previous) =>
        visibleSlides.length === 0 ? 0 : Math.min(previous, visibleSlides.length - 1),
      );
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

  const summaryAverage =
    reviewsMeta.total > 0 ? reviewsMeta.average : (product?.reviews?.average ?? 0);
  const summaryCount = reviewsMeta.total > 0 ? reviewsMeta.total : (product?.reviews?.count ?? 0);
  const favoriteButtonLabel =
    favoriteAction === 'saving'
      ? 'Veuillez patienter...'
      : isFavorite
        ? 'Retirer des favoris'
        : 'Ajouter aux favoris';
  const favoriteButtonDisabled = favoriteAction === 'saving' || favoriteStatus === 'loading';
  const variantOptions = useMemo(() => buildProductVariantOptions(colorVariants), [colorVariants]);
  const variantGroups = useMemo(() => groupProductVariants(variantOptions), [variantOptions]);

  const handleVariantChange = (variantId: string) => {
    const target = colorVariants.find((variant) => variant.id === Number(variantId));

    if (!target || target.id === product?.id) {
      return;
    }

    void navigate(`/catalogue/produits/${target.slug}`);
  };

  const handleAddFavorite = () => {
    if (!product) {
      return;
    }
    void toggleFavorite()
      .then(({ alreadyFavorite }) =>
        showToast(
          alreadyFavorite
            ? 'Ce produit est déjà présent dans vos favoris.'
            : 'Produit ajouté à vos favoris.',
        ),
      )
      .catch((error: unknown) => {
        const message =
          error instanceof Error ? error.message : "Impossible d'ajouter ce produit aux favoris.";
        showToast(message, { variant: 'error' });
      });
  };

  const handleRemoveFavorite = () => {
    if (!product) {
      return;
    }
    void toggleFavorite()
      .then(() => showToast('Produit retiré de vos favoris.'))
      .catch((error: unknown) => {
        const message =
          error instanceof Error ? error.message : 'Impossible de retirer ce produit des favoris.';
        showToast(message, { variant: 'error' });
      });
  };

  const handleNextSlide = () => {
    if (!product || visibleSlides.length <= 1) return;
    setActiveSlide((previous) => (previous + 1) % visibleSlides.length);
  };

  const handlePrevSlide = () => {
    if (!product || visibleSlides.length <= 1) return;
    setActiveSlide((previous) => (previous - 1 + visibleSlides.length) % visibleSlides.length);
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
            <ProductGallery
              activeSlide={activeSlide}
              onImageError={(url) => setFailedSlideUrls((previous) => new Set(previous).add(url))}
              onNextSlide={handleNextSlide}
              onPrevSlide={handlePrevSlide}
              onSelectSlide={setActiveSlide}
              slides={visibleSlides}
            />

            <ProductDetailHeader
              favoriteButtonDisabled={favoriteButtonDisabled}
              favoriteButtonLabel={favoriteButtonLabel}
              favoriteStatus={favoriteStatus}
              isAuthenticated={isAuthenticated}
              isFavorite={isFavorite}
              onAddFavorite={handleAddFavorite}
              onRemoveFavorite={handleRemoveFavorite}
              onVariantChange={handleVariantChange}
              product={product}
              productDisplayName={productDisplayName}
              summaryAverage={summaryAverage}
              summaryCount={summaryCount}
              variantGroups={variantGroups}
              variantOptions={variantOptions}
            />

            <ProductInfoHighlight product={product} productDates={productDates} />

            <ProductDescriptionSection description={product.description} />

            <ProductReviewsSection
              hasMoreReviews={hasMoreReviews}
              onLoadMoreReviews={loadMoreReviews}
              reviews={reviews}
              reviewsError={reviewsError}
              reviewsLoading={reviewsLoading}
              summaryAverage={summaryAverage}
              summaryCount={summaryCount}
            />
          </>
        )}
      </div>
    </SiteLayout>
  );
};
