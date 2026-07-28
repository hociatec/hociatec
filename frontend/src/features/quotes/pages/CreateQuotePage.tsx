import { SiteLayout } from '@/shared/components/layout/SiteLayout';
import { PageContainer } from '@/shared/components/layout/PageContainer';
import { FeedbackMessage } from '@/shared/components/ui/page-state';
import { ConfirmDialog } from '@/shared/components/feedback/ConfirmDialog';
import { PublicQuoteSelectionList } from '@/features/quotes/components/PublicQuoteSelectionList';
import { QuoteCatalogSelector } from '@/features/quotes/components/QuoteCatalogSelector';
import { QuoteCustomerFields } from '@/features/quotes/components/QuoteCustomerFields';
import { formatQuoteDate, formatQuotePrice } from '@/features/quotes/utils/quoteFormUtils';
import { useCreateQuote } from '@/features/quotes/hooks/useCreateQuote';
import { useDocumentTitle } from '@/shared/hooks/useDocumentTitle';

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
          <div className="md:col-span-2 space-y-6">
            <QuoteCustomerFields form={form} setForm={setForm} authenticated={status === 'authenticated'} />

            <QuoteCatalogSelector
              form={form}
              filteredServices={filteredServices}
              products={products}
              productLoading={productLoading}
              searchQuery={searchQuery}
              setSearchQuery={setSearchQuery}
              addProductLineFromProduct={addProductLineFromProduct}
              addServiceLine={addServiceLine}
              toggleServiceLine={toggleServiceLine}
              findProductItemIndex={findProductItemIndex}
              removeItem={removeItem}
              updateItem={updateItem}
              setRentalCandidate={setRentalCandidate}
              setRentalDialogOpen={setRentalDialogOpen}
            />
            <section>
              <div className="mt-8 space-y-3">
                <div>
                  <h3 className="text-lg font-semibold text-brand-900">Votre sélection</h3>
                  <p className="mt-1 text-sm text-stone-600">
                    Ajustez les quantités ou les durées avant d’enregistrer votre devis.
                  </p>
                </div>

                <PublicQuoteSelectionList
                  items={form.items}
                  onUpdateItem={updateItem}
                  onRemoveItem={removeItem}
                />
              </div>
            </section>
          </div>

          <div className="space-y-6">
            <section className="rounded-2xl border border-brand-100 bg-white p-5 shadow-sm">
              <h3 className="font-semibold text-brand-900">Estimation</h3>
              <p className="mt-1 text-sm text-stone-600">
                Montants calculés à partir de votre sélection.
              </p>
              <div className="mt-4 space-y-2 border-b border-brand-100 pb-4 text-sm text-stone-600">
                <div className="flex justify-between gap-4">
                  <span>Début de validité</span>
                  <strong>{formatQuoteDate(form.validFrom)}</strong>
                </div>
                <div className="flex justify-between gap-4">
                  <span>Fin de validité</span>
                  <strong>{formatQuoteDate(form.validUntil)}</strong>
                </div>
                {(form.discountCents ?? 0) > 0 && (
                  <div className="flex justify-between gap-4">
                    <span>Remise globale</span>
                    <strong>{formatQuotePrice(form.discountCents ?? 0)}</strong>
                  </div>
                )}
              </div>
              <div className="mt-4 space-y-2">
                <div className="flex justify-between gap-4 text-sm text-stone-600">
                  <span>Total HT</span>
                  <strong>{formatQuotePrice(totals.ht)}</strong>
                </div>
                <div className="flex justify-between gap-4 text-sm text-stone-600">
                  <span>TVA</span>
                  <strong>{formatQuotePrice(totals.vat)}</strong>
                </div>
                <div className="flex justify-between gap-4 border-t border-brand-100 pt-3 text-lg text-brand-900">
                  <span>Total TTC</span>
                  <strong>{formatQuotePrice(totals.ttc)}</strong>
                </div>
              </div>
            </section>

            <div className="grid gap-3">
              <button
                type="button"
                className="register-form__submit"
                onClick={() => void submit()}
                disabled={saving || (form.items ?? []).length === 0}
              >
                {saving ? 'Enregistrement...' : 'Enregistrer dans mon espace'}
              </button>
              <button
                type="button"
                className="hero__button hero__button--ghost"
                onClick={() => void handleDownloadPdf()}
                disabled={saving || (form.items ?? []).length === 0}
              >
                Télécharger le PDF
              </button>
            </div>
          </div>
        </div>

        <ConfirmDialog
          open={rentalDialogOpen && Boolean(rentalCandidate)}
          title="Ajouter une location au devis ?"
          description={
            <div>
              Voulez-vous vraiment ajouter le produit en location à{' '}
              <strong>{rentalCandidate?.name ?? ''}</strong> à votre devis ?
            </div>
          }
          confirmLabel="Oui, ajouter"
          cancelLabel="Non"
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
