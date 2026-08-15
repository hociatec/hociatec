import { useEffect, useState } from 'react';
import { useParams } from 'react-router';

import { emptyProductForm, type ProductFormState } from '@/features/admin/catalog/utils/productFormConfig';
import { formatVariantDetails } from '@/features/admin/catalog/utils/productFormUtils';
import {
  applyCategoryAttributeDefinitions,
  applyCategorySchemaToVariantRows,
  buildAttributesFromDefinitions,
} from '@/features/admin/catalog/utils/productFormUtils';
import { useProductGallery } from './useProductGallery';
import { useProductVariantRows } from './useProductVariantRows';
import { useProductBrandSelection } from './useProductBrandSelection';
import { useProductFormFields } from './useProductFormFields';
import { useProductFormLoader } from './useProductFormLoader';
import { useProductFormActions } from './useProductFormActions';
import { parseNullablePositiveInteger } from '@/shared/lib/parsers';
import type { AttributeRowState } from '@/features/admin/catalog/utils/productFormConfig';

export const useProductFormController = () => {
  const { productId } = useParams();
  const parsedProductId = parseNullablePositiveInteger(productId);
  const isEdit = parsedProductId !== null;
  const currentProductId = parsedProductId;

  const [form, setForm] = useState<ProductFormState>(emptyProductForm);
  const [error, setError] = useState<string | null>(null);
  const [message, setMessage] = useState<string | null>(null);
  const gallery = useProductGallery();
  const {
    variantRows,
    setVariantRows,
    addVariantRow,
    updateVariantRow,
    removeVariantRow,
    addVariantAttributeRow,
    updateVariantAttributeRow,
    removeVariantAttributeRow,
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

  const selectedCategory =
    categories.find((category) => category.id.toString() === form.categoryId) ?? null;

  useEffect(() => {
    if (!selectedCategory) {
      return;
    }

    const definitions = selectedCategory.attributeDefinitions ?? [];
    setForm((previous) => {
      const nextAttributes =
        definitions.length > 0
          ? applyCategoryAttributeDefinitions(previous.attributes, definitions)
          : previous.attributes;

      return nextAttributes === previous.attributes
        ? previous
        : { ...previous, attributes: nextAttributes };
    });
    setVariantRows((previous) =>
      definitions.length > 0 ? applyCategorySchemaToVariantRows(previous, definitions) : previous,
    );
  }, [selectedCategory, setForm, setVariantRows]);

  const resetForm = () => {
    setForm(emptyProductForm);
    gallery.reset();
    resetVariantRows();
    setBrandQuery('');
  };

  const addMainAttribute = () => {
    setForm((previous) => ({
      ...previous,
      attributes: [...previous.attributes, { code: '', label: '', value: '' }],
    }));
  };

  const updateMainAttribute = (
    index: number,
    field: keyof AttributeRowState,
    value: string,
  ) => {
    setForm((previous) => ({
      ...previous,
      attributes: previous.attributes.map((attribute, attributeIndex) =>
        attributeIndex === index ? { ...attribute, [field]: value } : attribute,
      ),
    }));
  };

  const removeMainAttribute = (index: number) => {
    setForm((previous) => ({
      ...previous,
      attributes: previous.attributes.filter((_, attributeIndex) => attributeIndex !== index),
    }));
  };

  const handleAddVariantRow = () => {
    addVariantRow(buildAttributesFromDefinitions(selectedCategory?.attributeDefinitions ?? []));
  };

  const actions = useProductFormActions({
    isEdit,
    form,
    brands,
    categories,
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
    addVariantRow: handleAddVariantRow,
    selectedCategory,
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
    addMainAttribute,
    updateMainAttribute,
    removeMainAttribute,
    handleSubmit: actions.handleSubmit,
    initialGallery: gallery.initialGallery,
    initialLoading,
    isEdit,
    message,
    navigateToProductList: actions.navigateToProductList,
    navigateToVariant: actions.navigateToVariant,
    removeVariantRow,
    addVariantAttributeRow,
    updateVariantAttributeRow,
    removeVariantAttributeRow,
    saving: actions.saving,
    updateVariantRow,
    variantRows,
  };
};
