import { SiteLayout } from '@/shared/components/layout/SiteLayout';
import { PageContainer } from '@/shared/components/layout/PageContainer';
import { FeedbackMessage } from '@/shared/components/ui/page-state';
import { useCreateQuote } from '@/features/quotes/hooks/useCreateQuote';
import { useDocumentTitle } from '@/shared/hooks/useDocumentTitle';
import {
  CreateQuoteMainColumn,
  QuotePageActions,
  QuoteRentalConfirmDialog,
  QuoteSummarySidebar,
} from '@/features/quotes/components/CreateQuotePageSections';

export const CreateQuotePage = () => {
  useDocumentTitle('Créer un devis');
  const {
    addProductLineFromProduct,
    addServiceLine,
    error,
    filteredServices,
    findProductItemIndex,
    form,
    handleDownloadPdf,
    message,
    products,
    productLoading,
    rentalCandidate,
    rentalDialogOpen,
    removeItem,
    saving,
    searchQuery,
    setForm,
    setRentalCandidate,
    setRentalDialogOpen,
    setSearchQuery,
    status,
    submit,
    totals,
    updateItem,
  } = useCreateQuote();
  const toggleServiceLine = (serviceId: number) => {
    setForm((current) => ({
      ...current,
      items: current.items.filter((item) => !(item.type === 'service' && item.serviceId === serviceId)),
    }));
  };
  return (
    <SiteLayout>
      <PageContainer size="wide" title="Créer un devis">
        {error && <FeedbackMessage>{error}</FeedbackMessage>}
        {message && <FeedbackMessage variant="success">{message}</FeedbackMessage>}

        <div className="quote-builder grid grid-cols-1 gap-8 md:grid-cols-3">
          <CreateQuoteMainColumn controller={{
            addProductLineFromProduct,
            addServiceLine,
            filteredServices,
            findProductItemIndex,
            form,
            products,
            productLoading,
            removeItem,
            searchQuery,
            setForm,
            setRentalCandidate,
            setRentalDialogOpen,
            setSearchQuery,
            status,
            updateItem,
          }} onToggleServiceLine={toggleServiceLine} />

          <div className="space-y-6">
            <QuoteSummarySidebar form={form} totals={totals} />
            <QuotePageActions
              disabled={saving || (form.items ?? []).length === 0}
              saving={saving}
              onSubmit={() => void submit()}
              onDownloadPdf={() => void handleDownloadPdf()}
            />
          </div>
        </div>

        <QuoteRentalConfirmDialog
          rentalDialogOpen={rentalDialogOpen}
          rentalCandidate={rentalCandidate}
          onCancel={() => {
            setRentalDialogOpen(false);
            setRentalCandidate(null);
          }}
          onConfirm={() => {
            if (rentalCandidate) {
              addProductLineFromProduct(rentalCandidate);
            }
            setRentalDialogOpen(false);
            setRentalCandidate(null);
          }}
        />
      </PageContainer>
    </SiteLayout>
  );
};
