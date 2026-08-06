import { useEffect, useState, type Dispatch, type SetStateAction } from 'react';
import { useQuery } from '@tanstack/react-query';

import {
  fetchAdminBrands,
  fetchAdminCategories,
  fetchAdminProduct,
  fetchAdminProducts,
  type CatalogBrand,
  type CatalogCategory,
  type CatalogProduct,
} from '@/features/catalog/adminApi';
import { emptyProductForm, type ProductFormState } from '@/features/admin/catalog/utils/productFormConfig';
import { buildProductFormState } from '@/features/admin/catalog/utils/productFormModel';
import { getHttpErrorMessage } from '@/shared/lib/httpClient';
import { adminCatalogQueryKeys } from '@/features/admin/catalog/queryKeys';
import { parseNullablePositiveInteger } from '@/shared/lib/parsers';

type UseProductFormLoaderParams = {
  isEdit: boolean;
  productId?: string | undefined;
  setForm: Dispatch<SetStateAction<ProductFormState>>;
  galleryHydrate: (gallery: Array<{ position: number; url: string }>) => void;
  resetVariantRows: () => void;
  onError: (message: string | null) => void;
  onMessage: (message: string | null) => void;
};

export const useProductFormLoader = ({
  isEdit,
  productId,
  setForm,
  galleryHydrate,
  resetVariantRows,
  onError,
  onMessage,
}: UseProductFormLoaderParams) => {
  const [categories, setCategories] = useState<CatalogCategory[]>([]);
  const [brands, setBrands] = useState<CatalogBrand[]>([]);
  const [groupVariants, setGroupVariants] = useState<CatalogProduct[]>([]);
  const [currentVariantPosition, setCurrentVariantPosition] = useState(1);
  const [loadedBrandQuery, setLoadedBrandQuery] = useState('');
  const parsedProductId = parseNullablePositiveInteger(productId);
  const hasValidProductId = isEdit && parsedProductId !== null;
  const safeProductId = parsedProductId ?? 0;
  const loaderQuery = useQuery({
    queryKey: adminCatalogQueryKeys.productForm(hasValidProductId ? safeProductId : null),
    queryFn: async () => {
      const [categoryList, brandList, product, adminProducts] = await Promise.all([
        fetchAdminCategories(),
        fetchAdminBrands(),
        hasValidProductId ? fetchAdminProduct(safeProductId) : Promise.resolve(null),
        isEdit ? fetchAdminProducts() : Promise.resolve([]),
      ]);
      return { categoryList, brandList, product, adminProducts };
    },
    enabled: hasValidProductId || !isEdit,
  });

  useEffect(() => {
    onError(null);
    onMessage(null);
    if (!loaderQuery.data) return;
    const { categoryList, brandList, product, adminProducts } = loaderQuery.data;

    setCategories(categoryList);
    setBrands(brandList);

    if (product) {
      hydrateProductForm({
        product,
        setCurrentVariantPosition,
        setForm,
        setLoadedBrandQuery,
        galleryHydrate,
        resetVariantRows,
      });
      setGroupVariants(resolveGroupVariants(product, adminProducts));
      return;
    }

    const firstCategory = categoryList[0];
    if (firstCategory) {
      setForm((previous) => ({ ...previous, categoryId: firstCategory.id.toString() }));
    } else {
      setForm(emptyProductForm);
    }
    setLoadedBrandQuery('');
    setGroupVariants([]);
  }, [
    galleryHydrate,
    loaderQuery.data,
    onError,
    onMessage,
    resetVariantRows,
    setForm,
  ]);

  useEffect(() => {
    if (loaderQuery.error) {
      onError(getHttpErrorMessage(loaderQuery.error, 'Impossible de charger les données du produit.'));
    }
  }, [loaderQuery.error, onError]);

  return {
    brands,
    categories,
    currentVariantPosition,
    groupVariants,
    initialLoading: loaderQuery.isLoading,
    loadedBrandQuery,
    setGroupVariants,
  };
};

const hydrateProductForm = ({
  product,
  setCurrentVariantPosition,
  setForm,
  setLoadedBrandQuery,
  galleryHydrate,
  resetVariantRows,
}: {
  product: CatalogProduct;
  setCurrentVariantPosition: (value: number) => void;
  setForm: Dispatch<SetStateAction<ProductFormState>>;
  setLoadedBrandQuery: (value: string) => void;
  galleryHydrate: (gallery: Array<{ position: number; url: string }>) => void;
  resetVariantRows: () => void;
}) => {
  setCurrentVariantPosition(product.variantPosition ?? 1);
  setForm(buildProductFormState(product));
  setLoadedBrandQuery(product.brand ?? '');
  galleryHydrate(product.gallery);
  resetVariantRows();
};

const resolveGroupVariants = (
  product: CatalogProduct,
  adminProducts: CatalogProduct[],
) => {
  if (!product.variantGroup) {
    return [product];
  }

  const relatedVariants = adminProducts
    .filter((item) => item.variantGroup === product.variantGroup)
    .sort((left, right) => {
      const leftPosition = left.variantPosition ?? Number.MAX_SAFE_INTEGER;
      const rightPosition = right.variantPosition ?? Number.MAX_SAFE_INTEGER;

      if (leftPosition !== rightPosition) {
        return leftPosition - rightPosition;
      }

      return left.id - right.id;
    });

  return relatedVariants.length > 0 ? relatedVariants : [product];
};
