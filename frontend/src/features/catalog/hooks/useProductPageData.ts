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

    if (product?.slug === slug) {
      setLoading(false);
      setError(null);
      return;
    }

    setLoading(true);
    setError(null);

    void fetchPublicProduct(slug)
      .then(setProduct)
      .catch((err: Error) => setError(err.message || 'Produit introuvable.'))
      .finally(() => setLoading(false));
  }, [slug, product?.slug]);

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
        const variants = items.filter((item) => buildVariantGroupKey(item) === variantGroup);
        setColorVariants(variants.length > 0 ? variants : [product]);
      })
      .catch(() => setColorVariants([product]));
  }, [product]);

  return { product, colorVariants, loading, error };
};
