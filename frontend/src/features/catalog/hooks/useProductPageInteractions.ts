import { useEffect, useMemo, useRef, useState } from 'react';
import { useNavigate } from 'react-router-dom';

import type { CatalogProduct } from '@/features/catalog/api';
import { useProductFavorite } from '@/features/catalog/hooks/useProductFavorite';
import { buildProductSlides, buildProductVariantOptions, formatProductDate, groupProductVariants } from '@/features/catalog/utils/productPageDisplay';
import { useToast } from '@/shared/components/ui/toast';

export const useProductPageInteractions = (product: CatalogProduct | null, colorVariants: CatalogProduct[], productDisplayName: string | null) => {
  const navigate = useNavigate();
  const { show: showToast } = useToast();
  const [activeSlide, setActiveSlide] = useState(0);
  const [failedSlideUrls, setFailedSlideUrls] = useState<Set<string>>(() => new Set());
  const previousSlidesSignatureRef = useRef('');
  const favorite = useProductFavorite(product?.id);
  const slides = useMemo(() => buildProductSlides(product, productDisplayName), [product, productDisplayName]);
  const visibleSlides = useMemo(() => slides.filter((slide) => !failedSlideUrls.has(slide.url)), [failedSlideUrls, slides]);

  useEffect(() => {
    const signature = visibleSlides.map((slide) => slide.url).join('|');
    if (signature === previousSlidesSignatureRef.current) {
      setActiveSlide((previous) => visibleSlides.length === 0 ? 0 : Math.min(previous, visibleSlides.length - 1));
      return;
    }
    previousSlidesSignatureRef.current = signature;
    setActiveSlide(0);
  }, [visibleSlides]);

  useEffect(() => setFailedSlideUrls(new Set()), [product?.slug]);

  const productDates = useMemo(() => product ? { created: formatProductDate(product.createdAt), updated: formatProductDate(product.updatedAt) } : null, [product]);
  const variantOptions = useMemo(() => buildProductVariantOptions(colorVariants), [colorVariants]);
  const variantGroups = useMemo(() => groupProductVariants(variantOptions), [variantOptions]);
  const favoriteButtonLabel = favorite.favoriteAction === 'saving' ? 'Veuillez patienter...' : favorite.isFavorite ? 'Retirer des favoris' : 'Ajouter aux favoris';
  const favoriteButtonDisabled = favorite.favoriteAction === 'saving' || favorite.favoriteStatus === 'loading';

  const handleVariantChange = (variantId: string) => {
    const target = colorVariants.find((variant) => variant.id === Number(variantId));
    if (target && target.id !== product?.id) void navigate(`/catalogue/produits/${target.slug}`);
  };
  const handleAddFavorite = () => {
    void favorite.toggle()
      .then(({ alreadyFavorite }) => showToast(alreadyFavorite ? 'Ce produit est déjà présent dans vos favoris.' : 'Produit ajouté à vos favoris.'))
      .catch((error: unknown) => showToast(error instanceof Error ? error.message : "Impossible d'ajouter ce produit aux favoris.", { variant: 'error' }));
  };
  const handleRemoveFavorite = () => {
    void favorite.toggle()
      .then(() => showToast('Produit retiré de vos favoris.'))
      .catch((error: unknown) => showToast(error instanceof Error ? error.message : 'Impossible de retirer le produit des favoris.', { variant: 'error' }));
  };
  const handleNextSlide = () => { if (product && visibleSlides.length > 1) setActiveSlide((previous) => (previous + 1) % visibleSlides.length); };
  const handlePrevSlide = () => { if (product && visibleSlides.length > 1) setActiveSlide((previous) => (previous - 1 + visibleSlides.length) % visibleSlides.length); };

  return { activeSlide, visibleSlides, productDates, variantOptions, variantGroups, favoriteButtonLabel, favoriteButtonDisabled, isAuthenticated: favorite.isAuthenticated, isFavorite: favorite.isFavorite, favoriteStatus: favorite.favoriteStatus, handleVariantChange, handleAddFavorite, handleRemoveFavorite, handleNextSlide, handlePrevSlide, setActiveSlide, setFailedSlideUrls };
};
