import { useEffect, useState } from 'react';

import { fetchPublicProduct, fetchPublicProducts, type CatalogProduct } from '../api';
import { buildVariantGroupKey } from '../utils/productPageDisplay';

export const useProductPageData = (slug?: string) => {
  const [product, setProduct] = useState<CatalogProduct | null>(null);
  const [colorVariants, setColorVariants] = useState<CatalogProduct[]>([]);
  const [loading, setLoading] = useState(true);
  const [error, setError] = useState<string | null>(null);

  useEffect(() => {
    if (!slug) return;
    const controller = new AbortController();

    if (product?.slug === slug) {
      setLoading(false);
      setError(null);
      return;
    }

    setLoading(true);
    setError(null);

    void fetchPublicProduct(slug, { signal: controller.signal })
      .then(setProduct)
      .catch((err: Error) => {
        if (!controller.signal.aborted) setError(err.message || 'Produit introuvable.');
      })
      .finally(() => {
        if (!controller.signal.aborted) setLoading(false);
      });

    return () => {
      controller.abort();
    };
  }, [slug, product?.slug]);

  useEffect(() => {
    if (!product) {
      setColorVariants([]);
      return;
    }

    const variantGroup = buildVariantGroupKey(product);
    const controller = new AbortController();

    void fetchPublicProducts({
      category: product.category.slug,
      sellingType: product.sellingType,
      sort: 'release_year_desc',
      perPage: 100,
      signal: controller.signal,
    })
      .then((items) => {
        if (controller.signal.aborted) return;
        const variants = items.filter((item) => buildVariantGroupKey(item) === variantGroup);
        setColorVariants(variants.length > 0 ? variants : [product]);
      })
      .catch(() => {
        if (!controller.signal.aborted) setColorVariants([product]);
      });

    return () => {
      controller.abort();
    };
  }, [product]);

  return { product, colorVariants, loading, error };
};
