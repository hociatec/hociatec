import { Link, useParams } from 'react-router';

import { ProductDescriptionSection } from '@/features/catalog/components/ProductDescriptionSection';
import {
  ProductDetailHeader,
  ProductGallery,
  ProductInfoHighlight,
  ProductReviewsSection,
} from '@/features/catalog/components/ProductPageSections';
import { useProductPageData } from '@/features/catalog/hooks/useProductPageData';
import { useProductPageInteractions } from '@/features/catalog/hooks/useProductPageInteractions';
import { useProductReviews } from '@/features/catalog/hooks/useProductReviews';
import { getCatalogProductDisplayName } from '@/features/catalog/utils/productDisplay';
import { buildProductStructuredData } from '@/features/catalog/utils/productPageDisplay';
import { SiteLayout } from '@/shared/components/layout/SiteLayout';
import { FeedbackMessage, LoadingState } from '@/shared/components/ui/page-state';
import { SITE_URL } from '@/shared/config/seoConfig';
import { useDocumentTitle } from '@/shared/hooks/useDocumentTitle';
import { useMetaTags } from '@/shared/hooks/useMetaTags';

import './CatalogPages.css';

export const ProductPage = () => {
  const { slug } = useParams<{ slug: string }>();
  const { product, colorVariants, loading, error } = useProductPageData(slug);
  const { hasMoreReviews, loadMoreReviews, reviews, reviewsError, reviewsLoading, reviewsMeta } = useProductReviews(product);
  const productDisplayName = product ? getCatalogProductDisplayName(product) : null;
  const canonicalUrl = product ? `${SITE_URL}/catalogue/produits/${product.slug}` : slug ? `${SITE_URL}/catalogue/produits/${slug}` : undefined;
  const productStructuredData =
    product && productDisplayName && canonicalUrl
      ? buildProductStructuredData(product, productDisplayName, canonicalUrl)
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

  const {
    activeSlide, visibleSlides, productDates, variantOptions, variantGroups,
    favoriteButtonLabel, favoriteButtonDisabled, isAuthenticated, isFavorite, favoriteStatus,
    handleVariantChange, handleAddFavorite, handleRemoveFavorite, handleNextSlide, handlePrevSlide,
    setActiveSlide, setFailedSlideUrls,
  } = useProductPageInteractions(product, colorVariants, productDisplayName);
  const summaryAverage = reviewsMeta.total > 0 ? reviewsMeta.average : (product?.reviews?.average ?? 0);
  const summaryCount = reviewsMeta.total > 0 ? reviewsMeta.total : (product?.reviews?.count ?? 0);

  return (
    <SiteLayout headerVariant="light">
      <div className="catalog-detail-layout">
        <Link to={product ? `/catalogue/${product.category.slug}` : '/'} className="catalog-page__breadcrumbs">Retour</Link>
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
