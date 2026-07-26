import { getHttpErrorMessage } from '@/shared/lib/httpClient';
import { type ChangeEvent, type FormEvent, useEffect, useState } from 'react';
import { useNavigate, useParams } from 'react-router-dom';

import {
  createProduct,
  deleteProduct,
  fetchAdminBrands,
  fetchAdminCategories,
  fetchAdminProduct,
  fetchAdminProducts,
  updateProduct,
  type CatalogBrand,
  type CatalogCategory,
  type CatalogProduct,
  type UpsertProductPayload,
} from '@/features/catalog/api';
import {
  emptyProductForm,
  type ProductFormState,
} from '@/features/admin/catalog/utils/productFormConfig';
import { formatVariantDetails, slugify } from '@/features/admin/catalog/utils/productFormUtils';
import {
  buildProductFormState,
  buildProductPayload,
} from '@/features/admin/catalog/utils/productFormModel';
import { useProductGallery } from './useProductGallery';
import { useProductVariantRows } from './useProductVariantRows';
import { useProductBrandSelection } from './useProductBrandSelection';

export const useProductFormController = () => {
  const { productId } = useParams();
  const isEdit = Boolean(productId);
  const currentProductId = productId ? Number(productId) : null;
  const navigate = useNavigate();

  const [form, setForm] = useState<ProductFormState>(emptyProductForm);
  const [categories, setCategories] = useState<CatalogCategory[]>([]);
  const [brands, setBrands] = useState<CatalogBrand[]>([]);
  const [saving, setSaving] = useState(false);
  const [deletingVariantId, setDeletingVariantId] = useState<number | null>(null);
  const [initialLoading, setInitialLoading] = useState(false);
  const [error, setError] = useState<string | null>(null);
  const [message, setMessage] = useState<string | null>(null);
  const [groupVariants, setGroupVariants] = useState<CatalogProduct[]>([]);
  const [currentVariantPosition, setCurrentVariantPosition] = useState(1);
  const gallery = useProductGallery();
  const {
    variantRows,
    addVariantRow,
    updateVariantRow,
    removeVariantRow,
    resetVariantRows,
  } = useProductVariantRows();
  const {
    brandQuery,
    setBrandQuery,
    filteredBrands,
    handleBrandQueryChange,
    handleBrandSelection,
  } = useProductBrandSelection(brands, form, setForm);

  useEffect(() => {
    setInitialLoading(true);
    setError(null);
    setMessage(null);

    const load = async () => {
      try {
        const [categoryList, brandList, product, adminProducts] = await Promise.all([
          fetchAdminCategories(),
          fetchAdminBrands(),
          isEdit ? fetchAdminProduct(Number(productId)) : Promise.resolve(null),
          isEdit ? fetchAdminProducts() : Promise.resolve([]),
        ]);

        setCategories(categoryList);
        setBrands(brandList);

        if (product) {
          hydrateFromProduct(product);
          if (product.variantGroup) {
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

            setGroupVariants(relatedVariants.length > 0 ? relatedVariants : [product]);
          } else {
            setGroupVariants([product]);
          }
        } else if (categoryList.length > 0) {
          setForm((prev) => ({ ...prev, categoryId: categoryList[0].id.toString() }));
          setBrandQuery('');
          setGroupVariants([]);
        }
      } catch (err) {
        setError(getHttpErrorMessage(err, 'Impossible de charger les données du produit.'));
      } finally {
        setInitialLoading(false);
      }
    };

    void load();
  }, [isEdit, productId]);

  const hydrateFromProduct = (product: CatalogProduct) => {
    const productBrand = product.brand ?? '';
    setCurrentVariantPosition(product.variantPosition ?? 1);
    setForm(buildProductFormState(product));
    setBrandQuery(productBrand);

    gallery.hydrate(product.gallery);
    resetVariantRows();
  };

  const handleFieldChange = (name: keyof ProductFormState, value: string) => {
    setForm((prev) => {
      if (name === 'name') {
        const generatedSlug = slugify(value);
        const shouldSyncSlug = prev.slug.trim() === '' || prev.slug === slugify(prev.name);
        return { ...prev, name: value, slug: shouldSyncSlug ? generatedSlug : prev.slug };
      }

      if (name === 'slug') {
        return { ...prev, slug: slugify(value) };
      }

      return { ...prev, [name]: value };
    });
  };

  const handleChange = (
    event: ChangeEvent<HTMLInputElement | HTMLTextAreaElement | HTMLSelectElement>,
  ) => {
    const { name, value, type, checked } = event.target as HTMLInputElement;

    if (type === 'checkbox') {
      setForm((prev) => ({ ...prev, [name]: checked }));
      return;
    }

    handleFieldChange(name as keyof ProductFormState, value);
  };

  const handleGalleryFileChange = gallery.onFileChange;
  const handleRemoveGallery = gallery.remove;

  const handleDeleteVariant = (variant: CatalogProduct) => {
    if (groupVariants.length <= 1 || deletingVariantId !== null) return;

    if (!window.confirm(`Supprimer la variante ${formatVariantDetails(variant)} ?`)) return;

    setDeletingVariantId(variant.id);
    setError(null);
    setMessage(null);
    void deleteProduct(variant.id)
      .then(() => {
        const remainingVariants = groupVariants.filter((item) => item.id !== variant.id);
        if (variant.id === currentProductId) {
          const nextVariant = remainingVariants[0] ?? null;
          void navigate(
            nextVariant
              ? `/admin/catalog/products/${nextVariant.id}/edit`
              : '/admin/catalog/products',
          );
          return;
        }

        setGroupVariants(remainingVariants);
        setMessage('Variante supprimée.');
      })
      .catch((err) => setError(getHttpErrorMessage(err, 'Impossible de supprimer la variante.')))
      .finally(() => setDeletingVariantId(null));
  };

  const handleSubmit = (event: FormEvent<HTMLFormElement>) => {
    event.preventDefault();
    if (saving) return;
    const result = buildProductPayload({
      form,
      brands,
      variantRows,
      groupVariants,
      currentProductId,
      galleryFiles: gallery.galleryFiles,
      galleryToRemove: gallery.galleryToRemove,
    });
    if (result.error) return setError(result.error);
    if (!result.payload) return setError('Le formulaire produit est incomplet.');
    const payload: UpsertProductPayload = result.payload;

    setSaving(true);
    setError(null);
    setMessage(null);
    const action = isEdit ? updateProduct(Number(productId), payload) : createProduct(payload);
    void action
      .then(() => {
        setMessage(isEdit ? 'Produit mis à jour.' : 'Produit créé.');
        if (!isEdit) {
          setForm(emptyProductForm);
          gallery.reset();
          resetVariantRows();
        }
        setTimeout(() => navigate('/admin/catalog/products'), 800);
      })
      .catch((err) => setError(getHttpErrorMessage(err, "Impossible d'enregistrer le produit.")))
      .finally(() => setSaving(false));
  };

  const navigateToProductList = () => navigate('/admin/catalog/products');
  const navigateToVariant = (variantId: number) => {
    setError(null);
    setMessage(null);
    void navigate(`/admin/catalog/products/${variantId}/edit`);
  };

  return {
    addVariantRow,
    brandQuery,
    categories,
    currentProductId,
    currentVariantPosition,
    deletingVariantId,
    error,
    filteredBrands,
    form,
    formatVariantDetails,
    galleryFiles: gallery.galleryFiles,
    galleryPreviews: gallery.galleryPreviews,
    galleryToRemove: gallery.galleryToRemove,
    groupVariants,
    handleBrandQueryChange,
    handleBrandSelection,
    handleChange,
    handleDeleteVariant,
    handleGalleryFileChange,
    handleRemoveGallery,
    handleSubmit,
    initialGallery: gallery.initialGallery,
    initialLoading,
    isEdit,
    message,
    navigateToProductList,
    navigateToVariant,
    removeVariantRow,
    saving,
    updateVariantRow,
    variantRows,
  };
};
