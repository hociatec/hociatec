import { PageContainer } from '@/shared/components/PageContainer';
import { useDocumentTitle } from '@/shared/hooks/useDocumentTitle';
import { ConfirmDialog } from '@/shared/components/ConfirmDialog';
import { QuoteEditorGrid } from '@/features/admin/extracted/quotes/AdminQuoteFormSections';
import { useAdminQuoteFormController } from '@/features/admin/extracted/quotes/useAdminQuoteFormController';
import { FeedbackMessage, LoadingState } from '@/shared/components/ui/page-state';

export const QuoteFormPage = () => {
  const controller = useAdminQuoteFormController();
  useDocumentTitle('Admin - Devis');

  return (
    <PageContainer size="admin"
      title={controller.quote?.number ? `Devis ${controller.quote.number}` : 'Nouveau devis'}
      headerActions={
        !controller.isNew ? (
          <div className="catalog-admin-actions">
            <button type="button" className="catalog-admin-actions__edit" onClick={() => void controller.handleGeneratePdf()}>
              Télécharger
            </button>
          </div>
        ) : undefined
      }
    >
      {controller.error && <FeedbackMessage>{controller.error}</FeedbackMessage>}
      {controller.message && <FeedbackMessage variant="success">{controller.message}</FeedbackMessage>}

      {controller.loading || !controller.quote ? (
        <LoadingState>Chargement...</LoadingState>
      ) : (
        <QuoteEditorGrid
          filteredProducts={controller.filteredProducts}
          filteredServices={controller.filteredServices}
          onAddCustomItem={controller.addCustomItem}
          onAddItemFromProduct={controller.addItemFromProduct}
          onAddItemFromService={controller.addItemFromService}
          onRemoveItem={controller.removeItem}
          onUpdateItem={controller.updateItem}
          products={controller.products}
          quote={controller.quote}
          searchQuery={controller.searchQuery}
          setQuote={controller.setQuote}
          setRentalCandidate={controller.setRentalCandidate}
          setRentalDialogOpen={controller.setRentalDialogOpen}
          setSearchQuery={controller.setSearchQuery}
          total={controller.total}
          trimmedSearchQuery={controller.trimmedSearchQuery}
        />
      )}

      {!controller.loading && controller.quote ? (
        <div className="mt-8 flex items-center justify-end gap-3">
          {!controller.isNew && (
            <button type="button" className="catalog-admin-actions__edit" onClick={() => void controller.handleGeneratePdf()}>
              Télécharger
            </button>
          )}
          <button type="button" className="register-form__submit" onClick={() => void controller.save()} disabled={controller.saving}>
            {controller.saving ? 'Sauvegarde...' : 'Enregistrer'}
          </button>
        </div>
      ) : null}

      <ConfirmDialog
        open={controller.rentalDialogOpen && Boolean(controller.rentalCandidate)}
        title="Ajouter une location au devis ?"
        description={
          <div>
            Voulez-vous vraiment ajouter le produit en location à <strong>{controller.rentalCandidate?.name ?? ''}</strong> à votre devis ?
          </div>
        }
        confirmLabel="Oui, ajouter"
        cancelLabel="Non"
        onCancel={controller.cancelRentalAdd}
        onConfirm={controller.confirmRentalAdd}
      />
    </PageContainer>
  );
};
