import { PageContainer } from '@/shared/components/PageContainer';
import { useDocumentTitle } from '@/shared/hooks/useDocumentTitle';
import {
  ProductContentMediaSection,
  ProductDiscountSection,
  ProductGeneralSection,
  VariantSwitcherSection,
} from '@/features/admin/catalog/components/ProductFormContentSections';
import { ProductCurrentVariantSection, ProductExtraVariantsSection } from '@/features/admin/catalog/components/ProductVariantSections';
import { ProductFormDatalists } from '@/features/admin/catalog/components/ProductFormDatalists';
import { ProductPublicationSection } from '@/features/admin/catalog/components/ProductPublicationSection';
import { useProductFormController } from '@/features/admin/catalog/hooks/useProductFormControllerRefactored';
import { FeedbackMessage, LoadingState } from '@/shared/components/ui/page-state';

import '@/features/catalog/pages/CatalogPages.css';

export const ProductFormPage = () => {
  const controller = useProductFormController();

  useDocumentTitle(controller.isEdit ? 'Admin - Modifier un produit' : 'Admin - Nouveau produit');

  return (
    <PageContainer size="admin"
      title={controller.isEdit ? 'Modifier un produit' : 'Nouveau produit'}
      headerActions={
        <button
          type="button"
          className="catalog-admin-actions__edit"
          onClick={controller.navigateToProductList}
        >
          Retour à la liste
        </button>
      }
    >
      {controller.error && <FeedbackMessage>{controller.error}</FeedbackMessage>}
      {controller.message && <FeedbackMessage variant="success">{controller.message}</FeedbackMessage>}

      {controller.initialLoading ? (
        <LoadingState>Chargement du produit...</LoadingState>
      ) : (
        <form className="catalog-form-grid" onSubmit={controller.handleSubmit}>
          {controller.isEdit && controller.groupVariants.length > 1 && (
            <VariantSwitcherSection
              currentProductId={controller.currentProductId}
              deletingVariantId={controller.deletingVariantId}
              groupVariants={controller.groupVariants}
              formatVariantDetails={controller.formatVariantDetails}
              onDeleteVariant={controller.handleDeleteVariant}
              onNavigateVariant={controller.navigateToVariant}
            />
          )}

          <ProductGeneralSection
            brandQuery={controller.brandQuery}
            categories={controller.categories}
            filteredBrands={controller.filteredBrands}
            form={controller.form}
            onBrandQueryChange={controller.handleBrandQueryChange}
            onBrandSelection={controller.handleBrandSelection}
            onChange={controller.handleChange}
          />
          <ProductFormDatalists />
          <ProductDiscountSection form={controller.form} onChange={controller.handleChange} />
          <ProductCurrentVariantSection
            currentVariantPosition={controller.currentVariantPosition}
            form={controller.form}
            onChange={controller.handleChange}
          />
          <ProductExtraVariantsSection
            rows={controller.variantRows}
            onAdd={controller.addVariantRow}
            onRemove={controller.removeVariantRow}
            onUpdate={controller.updateVariantRow}
          />
          <ProductContentMediaSection
            form={controller.form}
            galleryFiles={controller.galleryFiles}
            galleryPreviews={controller.galleryPreviews}
            galleryToRemove={controller.galleryToRemove}
            initialGallery={controller.initialGallery}
            onChange={controller.handleChange}
            onGalleryFileChange={controller.handleGalleryFileChange}
            onRemoveGallery={controller.handleRemoveGallery}
          />
          <ProductPublicationSection form={controller.form} onChange={controller.handleChange} />

          <div className="catalog-form-actions">
            <button className="register-form__submit" type="submit" disabled={controller.saving}>
              {controller.saving ? 'Enregistrement...' : controller.isEdit ? 'Mettre à jour' : 'Créer'}
            </button>
          </div>
        </form>
      )}
    </PageContainer>
  );
};
