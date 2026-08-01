import { PageContainer } from '@/shared/components/layout/PageContainer';
import { useDocumentTitle } from '@/shared/hooks/useDocumentTitle';
import { useProductFormController } from '@/features/admin/catalog/hooks/useProductFormControllerRefactored';
import { LoadingState } from '@/shared/components/ui/page-state';
import {
  ProductFormAlerts,
  ProductFormContent,
  ProductFormHeaderAction,
} from '@/features/admin/catalog/components/ProductFormPageSections';

import '@/features/catalog/pages/CatalogPages.css';

export const ProductFormPage = () => {
  const controller = useProductFormController();

  useDocumentTitle(controller.isEdit ? 'Admin - Modifier un produit' : 'Admin - Nouveau produit');

  return (
    <PageContainer
      size="admin"
      title={controller.isEdit ? 'Modifier un produit' : 'Nouveau produit'}
      headerActions={
        <ProductFormHeaderAction onBack={controller.navigateToProductList} />
      }
    >
      <ProductFormAlerts error={controller.error} message={controller.message} />

      {controller.initialLoading ? (
        <LoadingState>Chargement du produit...</LoadingState>
      ) : (
        <ProductFormContent controller={controller} />
      )}
    </PageContainer>
  );
};
