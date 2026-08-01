import {
  ProductGeneralSection,
} from '@/features/admin/catalog/components/ProductFormContentSections';
import { VariantSwitcherSection } from '@/features/admin/catalog/components/VariantSwitcherSection';
import { ProductDiscountSection } from '@/features/admin/catalog/components/ProductDiscountSection';
import { ProductContentMediaSection } from '@/features/admin/catalog/components/ProductContentMediaSection';
import {
  ProductCurrentVariantSection,
  ProductExtraVariantsSection,
} from '@/features/admin/catalog/components/ProductVariantSections';
import { ProductFormDatalists } from '@/features/admin/catalog/components/ProductFormDatalists';
import { ProductPublicationSection } from '@/features/admin/catalog/components/ProductPublicationSection';
import { useProductFormController } from '@/features/admin/catalog/hooks/useProductFormControllerRefactored';
import { FeedbackMessage } from '@/shared/components/ui/page-state';

type ProductFormController = ReturnType<typeof useProductFormController>;

export const ProductFormHeaderAction = ({
  onBack,
}: {
  onBack: () => void;
}) => (
  <button
    type="button"
    className="catalog-admin-actions__edit"
    onClick={onBack}
  >
    Retour à la liste
  </button>
);

export const ProductFormAlerts = ({
  error,
  message,
}: Pick<ProductFormController, 'error' | 'message'>) => (
  <>
    {error && <FeedbackMessage>{error}</FeedbackMessage>}
    {message && <FeedbackMessage variant="success">{message}</FeedbackMessage>}
  </>
);

export const ProductFormContent = ({
  controller,
}: {
  controller: ProductFormController;
}) => (
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
        {controller.saving
          ? 'Enregistrement...'
          : controller.isEdit
            ? 'Mettre à jour'
            : 'Créer'}
      </button>
    </div>
  </form>
);
