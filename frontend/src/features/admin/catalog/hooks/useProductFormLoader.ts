import { useEffect, useState, type Dispatch, type SetStateAction } from 'react';

import {
  fetchAdminBrands,
  fetchAdminCategories,
  fetchAdminProduct,
  fetchAdminProducts,
  type CatalogBrand,
  type CatalogCategory,
  type CatalogProduct,
} from '@/features/catalog/api';
import { emptyProductForm, type ProductFormState } from '@/features/admin/catalog/utils/productFormConfig';
import { buildProductFormState } from '@/features/admin/catalog/utils/productFormModel';
import { getHttpErrorMessage } from '@/shared/lib/httpClient';

type UseProductFormLoaderParams = {
  isEdit: boolean;
  productId?: string;
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
  const [initialLoading, setInitialLoading] = useState(false);
  const [loadedBrandQuery, setLoadedBrandQuery] = useState('');

  useEffect(() => {
    setInitialLoading(true);
    onError(null);
    onMessage(null);

    const load = async () => {
      try {
        const [categoryList, brandList, product, adminProducts] = await Promise.all([
          fetchAdminCategories(),
          fetchAdminBrands(),
          isEdit && productId ? fetchAdminProduct(Number(productId)) : Promise.resolve(null),
          isEdit ? fetchAdminProducts() : Promise.resolve([]),
        ]);

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

        if (categoryList.length > 0) {
          setForm((previous) => ({ ...previous, categoryId: categoryList[0].id.toString() }));
        } else {
          setForm(emptyProductForm);
        }
        setLoadedBrandQuery('');
        setGroupVariants([]);
      } catch (error) {
        onError(getHttpErrorMessage(error, 'Impossible de charger les données du produit.'));
      } finally {
        setInitialLoading(false);
      }
    };

    void load();
  }, [
    galleryHydrate,
    isEdit,
    onError,
    onMessage,
    productId,
    resetVariantRows,
    setForm,
  ]);

  return {
    brands,
    categories,
    currentVariantPosition,
    groupVariants,
    initialLoading,
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
