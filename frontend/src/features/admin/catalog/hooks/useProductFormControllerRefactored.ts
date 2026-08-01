import { useEffect, useState } from 'react';
import { useParams } from 'react-router';

import { emptyProductForm, type ProductFormState } from '@/features/admin/catalog/utils/productFormConfig';
import { formatVariantDetails } from '@/features/admin/catalog/utils/productFormUtils';
import { useProductGallery } from './useProductGallery';
import { useProductVariantRows } from './useProductVariantRows';
import { useProductBrandSelection } from './useProductBrandSelection';
import { useProductFormFields } from './useProductFormFields';
import { useProductFormLoader } from './useProductFormLoader';
import { useProductFormActions } from './useProductFormActions';

export const useProductFormController = () => {
  const { productId } = useParams();
  const isEdit = Boolean(productId);
  const currentProductId = productId ? Number(productId) : null;

  const [form, setForm] = useState<ProductFormState>(emptyProductForm);
  const [error, setError] = useState<string | null>(null);
  const [message, setMessage] = useState<string | null>(null);
  const gallery = useProductGallery();
  const {
    variantRows,
    addVariantRow,
    updateVariantRow,
    removeVariantRow,
    resetVariantRows,
  } = useProductVariantRows();
  const {
    brands,
    categories,
    currentVariantPosition,
    groupVariants,
    initialLoading,
    loadedBrandQuery,
    setGroupVariants,
  } = useProductFormLoader({
    isEdit,
    productId,
    setForm,
    galleryHydrate: gallery.hydrate,
    resetVariantRows,
    onError: setError,
    onMessage: setMessage,
  });

  const brandSelection = useProductBrandSelection(brands, form, setForm);
  const { handleChange } = useProductFormFields(setForm);
  const { brandQuery, filteredBrands, handleBrandQueryChange, handleBrandSelection, setBrandQuery } =
    brandSelection;

  useEffect(() => {
    setBrandQuery(loadedBrandQuery);
  }, [loadedBrandQuery, setBrandQuery]);

  const resetForm = () => {
    setForm(emptyProductForm);
    gallery.reset();
    resetVariantRows();
    setBrandQuery('');
  };

  const actions = useProductFormActions({
    isEdit,
    productId,
    form,
    brands,
    variantRows,
    groupVariants,
    currentProductId,
    galleryFiles: gallery.galleryFiles,
    galleryToRemove: gallery.galleryToRemove,
    resetForm,
    onError: setError,
    onMessage: setMessage,
    setGroupVariants,
  });

  return {
    addVariantRow,
    brandQuery,
    categories,
    currentProductId,
    currentVariantPosition,
    deletingVariantId: actions.deletingVariantId,
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
    handleDeleteVariant: actions.handleDeleteVariant,
    handleGalleryFileChange: gallery.onFileChange,
    handleRemoveGallery: gallery.remove,
    handleSubmit: actions.handleSubmit,
    initialGallery: gallery.initialGallery,
    initialLoading,
    isEdit,
    message,
    navigateToProductList: actions.navigateToProductList,
    navigateToVariant: actions.navigateToVariant,
    removeVariantRow,
    saving: actions.saving,
    updateVariantRow,
    variantRows,
  };
};
