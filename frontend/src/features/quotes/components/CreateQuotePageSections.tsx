import type { Dispatch, SetStateAction } from 'react';

import { ConfirmDialog } from '@/shared/components/feedback/ConfirmDialog';
import { PublicQuoteSelectionList } from '@/features/quotes/components/PublicQuoteSelectionList';
import { QuoteCustomerFields } from '@/features/quotes/components/QuoteCustomerFields';
import { QuoteCatalogSelector } from '@/features/quotes/components/QuoteCatalogSelector';
import { formatQuoteDate, formatQuotePrice, type QuoteItem } from '@/features/quotes/utils/quoteFormUtils';
import type { CatalogProduct } from '@/features/catalog/publicApi';
import type { QuoteDraft } from '@/features/quotes/hooks/useCreateQuote';
import type { QuoteServiceDto } from '@/features/quotes/types/quoteTypes';

type CreateQuoteMainController = {
  addProductLineFromProduct: (product: CatalogProduct) => void;
  addServiceLine: (serviceId: number) => void;
  filteredServices: QuoteServiceDto[];
  findProductItemIndex: (productId: number) => number;
  form: QuoteDraft;
  products: CatalogProduct[];
  productLoading: boolean;
  removeItem: (index: number) => void;
  searchQuery: string;
  setForm: Dispatch<SetStateAction<QuoteDraft>>;
  setRentalCandidate: (product: CatalogProduct | null) => void;
  setRentalDialogOpen: (open: boolean) => void;
  setSearchQuery: (value: string) => void;
  status: string;
  updateItem: (index: number, patch: Partial<QuoteItem>) => void;
};

type QuoteTotals = {
  ht: number;
  vat: number;
  ttc: number;
};

export const CreateQuoteMainColumn = ({
  controller,
  onToggleServiceLine,
}: {
  controller: CreateQuoteMainController;
  onToggleServiceLine: (serviceId: number) => void;
}) => (
  <div className="md:col-span-2 space-y-6">
    <QuoteCustomerFields
      form={controller.form}
      setForm={controller.setForm}
      authenticated={controller.status === 'authenticated'}
    />

    <QuoteCatalogSelector
      form={controller.form}
      filteredServices={controller.filteredServices}
      products={controller.products}
      productLoading={controller.productLoading}
      searchQuery={controller.searchQuery}
      setSearchQuery={controller.setSearchQuery}
      addProductLineFromProduct={controller.addProductLineFromProduct}
      addServiceLine={controller.addServiceLine}
      toggleServiceLine={onToggleServiceLine}
      findProductItemIndex={controller.findProductItemIndex}
      removeItem={controller.removeItem}
      updateItem={controller.updateItem}
      setRentalCandidate={controller.setRentalCandidate}
      setRentalDialogOpen={controller.setRentalDialogOpen}
    />

    <QuoteSelectionSection
      items={controller.form.items}
      onUpdateItem={controller.updateItem}
      onRemoveItem={controller.removeItem}
    />
  </div>
);

export const QuoteSelectionSection = ({
  items,
  onUpdateItem,
  onRemoveItem,
}: {
  items: QuoteDraft['items'];
  onUpdateItem: (index: number, patch: Partial<QuoteItem>) => void;
  onRemoveItem: (index: number) => void;
}) => (
  <section>
    <div className="mt-8 space-y-3">
      <div>
        <h3 className="text-lg font-semibold text-brand-900">Votre sélection</h3>
        <p className="mt-1 text-sm text-stone-600">
          Ajustez les quantités ou les durées avant d’enregistrer votre devis.
        </p>
      </div>

      <PublicQuoteSelectionList
        items={items}
        onUpdateItem={onUpdateItem}
        onRemoveItem={onRemoveItem}
      />
    </div>
  </section>
);

export const QuoteSummarySidebar = ({
  form,
  totals,
}: {
  form: QuoteDraft;
  totals: QuoteTotals;
}) => (
  <section className="rounded-2xl border border-brand-100 bg-white p-5 shadow-sm">
    <h3 className="font-semibold text-brand-900">Estimation</h3>
    <p className="mt-1 text-sm text-stone-600">Montants calculés à partir de votre sélection.</p>
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
);

export const QuotePageActions = ({
  disabled,
  saving,
  onSubmit,
  onDownloadPdf,
}: {
  disabled: boolean;
  saving: boolean;
  onSubmit: () => void;
  onDownloadPdf: () => void;
}) => (
  <div className="grid gap-3">
    <button
      type="button"
      className="register-form__submit"
      onClick={onSubmit}
      disabled={disabled}
    >
      {saving ? 'Enregistrement...' : 'Enregistrer dans mon espace'}
    </button>
    <button
      type="button"
      className="hero__button hero__button--ghost"
      onClick={onDownloadPdf}
      disabled={disabled}
    >
      Télécharger le PDF
    </button>
  </div>
);

export const QuoteRentalConfirmDialog = ({
  rentalDialogOpen,
  rentalCandidate,
  onCancel,
  onConfirm,
}: {
  rentalDialogOpen: boolean;
  rentalCandidate: CatalogProduct | null;
  onCancel: () => void;
  onConfirm: () => void;
}) => (
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
    onCancel={onCancel}
    onConfirm={onConfirm}
  />
);
