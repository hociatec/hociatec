import type { Dispatch, FormEvent, SetStateAction } from 'react';
import { useNavigate } from 'react-router';
import { useMutation, useQueryClient } from '@tanstack/react-query';

import { createProduct, deleteProduct, updateProduct, type CatalogBrand, type CatalogProduct, type UpsertProductPayload } from '@/features/catalog/adminApi';
import { type ProductFormState, type VariantRowState } from '@/features/admin/catalog/utils/productFormConfig';
import { formatVariantDetails } from '@/features/admin/catalog/utils/productFormUtils';
import { buildProductPayload } from '@/features/admin/catalog/utils/productFormModel';
import { getHttpErrorMessage } from '@/shared/lib/httpClient';
import { adminCatalogQueryKeys } from '@/shared/lib/queryKeys';

type UseProductFormActionsParams = {
  isEdit: boolean;
  productId?: string | undefined;
  form: ProductFormState;
  brands: CatalogBrand[];
  variantRows: VariantRowState[];
  groupVariants: CatalogProduct[];
  currentProductId: number | null;
  galleryFiles: Array<File | null>;
  galleryToRemove: number[];
  resetForm: () => void;
  onError: (message: string | null) => void;
  onMessage: (message: string | null) => void;
  setGroupVariants: Dispatch<SetStateAction<CatalogProduct[]>>;
};

export const useProductFormActions = ({
  isEdit,
  productId,
  form,
  brands,
  variantRows,
  groupVariants,
  currentProductId,
  galleryFiles,
  galleryToRemove,
  resetForm,
  onError,
  onMessage,
  setGroupVariants,
}: UseProductFormActionsParams) => {
  const navigate = useNavigate();
  const queryClient = useQueryClient();
  const deleteVariantMutation = useMutation({
    mutationFn: deleteProduct,
    onSuccess: (response, variantId) => {
      void queryClient.invalidateQueries({ queryKey: adminCatalogQueryKeys.products() });
      const remainingVariants = groupVariants.filter((item) => item.id !== variantId);
      if (variantId === currentProductId) {
        const nextVariant = remainingVariants[0] ?? null;
        void navigate(
          nextVariant
            ? `/admin/catalog/products/${nextVariant.id}/edit`
            : '/admin/catalog/products',
        );
        return;
      }
      setGroupVariants(remainingVariants);
      onMessage(response.message ?? 'La variante a bien été supprimée.');
    },
    onError: (error) => onError(getHttpErrorMessage(error, 'Impossible de supprimer la variante.')),
  });
  const saveMutation = useMutation({
    mutationFn: (payload: UpsertProductPayload) =>
      isEdit && productId ? updateProduct(Number(productId), payload) : createProduct(payload),
    onSuccess: () => {
      void queryClient.invalidateQueries({ queryKey: adminCatalogQueryKeys.products() });
      onMessage(isEdit ? 'Produit mis à jour.' : 'Produit créé.');
      if (!isEdit) {
        resetForm();
      }
      setTimeout(() => navigate('/admin/catalog/products'), 800);
    },
    onError: (error) => onError(getHttpErrorMessage(error, "Impossible d'enregistrer le produit.")),
  });

  const handleDeleteVariant = (variant: CatalogProduct) => {
    if (groupVariants.length <= 1 || deleteVariantMutation.isPending) return;
    if (!window.confirm(`Supprimer la variante ${formatVariantDetails(variant)} ?`)) return;

    onError(null);
    onMessage(null);
    deleteVariantMutation.mutate(variant.id);
  };

  const handleSubmit = (event: FormEvent<HTMLFormElement>) => {
    event.preventDefault();
    if (saveMutation.isPending) return;

    const result = buildProductPayload({
      form,
      brands,
      variantRows,
      groupVariants,
      currentProductId,
      galleryFiles,
      galleryToRemove,
    });

    if (result.error) {
      onError(result.error);
      return;
    }

    if (!result.payload) {
      onError('Le formulaire produit est incomplet.');
      return;
    }

    const payload: UpsertProductPayload = result.payload;
    onError(null);
    onMessage(null);
    saveMutation.mutate(payload);
  };

  const navigateToProductList = () => navigate('/admin/catalog/products');

  const navigateToVariant = (variantId: number) => {
    onError(null);
    onMessage(null);
    void navigate(`/admin/catalog/products/${variantId}/edit`);
  };

  return {
    deletingVariantId: deleteVariantMutation.variables ?? null,
    handleDeleteVariant,
    handleSubmit,
    navigateToProductList,
    navigateToVariant,
    saving: saveMutation.isPending,
  };
};
