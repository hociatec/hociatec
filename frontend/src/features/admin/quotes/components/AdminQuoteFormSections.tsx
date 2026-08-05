import { type Dispatch, type SetStateAction } from 'react';

import { AdminQuoteItemsTable } from '@/features/admin/quotes/components/AdminQuoteItemsTable';
import { AdminQuoteCatalogSearchResults } from './AdminQuoteCatalogSearchResults';
import { type CatalogProduct } from '@/features/catalog/api';
import type { QuoteServiceDto } from '@/features/quotes/types/quoteTypes';
import { type QuoteItem } from '@/features/quotes/utils/quoteFormUtils';
import { AdminQuoteCustomerFields, AdminQuoteSettingsSummary } from './AdminQuoteCustomerAndSettings';
import type { AdminQuoteFormState } from '@/features/admin/quotes/types/adminQuoteFormTypes';

type QuoteEditorGridProps = {
  filteredProducts: CatalogProduct[];
  filteredServices: QuoteServiceDto[];
  onAddCustomItem: () => void;
  onAddItemFromProduct: (productId: number) => void;
  onAddItemFromService: (serviceId: number) => void;
  onRemoveItem: (index: number) => void;
  onUpdateItem: (index: number, patch: Partial<QuoteItem>) => void;
  products: CatalogProduct[];
  quote: AdminQuoteFormState;
  searchQuery: string;
  setQuote: Dispatch<SetStateAction<AdminQuoteFormState | null>>;
  setRentalCandidate: (product: CatalogProduct) => void;
  setRentalDialogOpen: (open: boolean) => void;
  setSearchQuery: (value: string) => void;
  total: { ht: number; vat: number; ttc: number };
  trimmedSearchQuery: string;
};

export const QuoteEditorGrid = ({
  filteredProducts,
  filteredServices,
  onAddCustomItem,
  onAddItemFromProduct,
  onAddItemFromService,
  onRemoveItem,
  onUpdateItem,
  products,
  quote,
  searchQuery,
  setQuote,
  setRentalCandidate,
  setRentalDialogOpen,
  setSearchQuery,
  total,
  trimmedSearchQuery,
}: QuoteEditorGridProps) => (
  <div className="grid grid-cols-1 gap-8 md:grid-cols-3">
    <div className="md:col-span-2 space-y-6">
      <AdminQuoteCustomerFields quote={quote} setQuote={setQuote} total={total} />

      <section>
        <div className="mb-2 flex flex-wrap items-center justify-between gap-3">
          <h3 className="font-semibold">Éléments du devis</h3>
          <button type="button" className="catalog-admin-actions__edit" onClick={onAddCustomItem}>
            Ajouter une ligne manuelle
          </button>
        </div>
        <div className="mb-4 space-y-2">
          <input
            type="search"
            placeholder="Rechercher un produit ou un service..."
            value={searchQuery}
            onChange={(e) => setSearchQuery(e.target.value)}
          />
          {trimmedSearchQuery === '' && (
            <p className="text-sm text-stone-500">
              Lance une recherche pour afficher les produits et services à ajouter au devis. Utilise
              le bouton Ajouter une ligne manuelle si l’élément n’est pas encore au catalogue.
            </p>
          )}
        </div>
        <AdminQuoteCatalogSearchResults
          filteredProducts={filteredProducts}
          filteredServices={filteredServices}
          onAddItemFromProduct={onAddItemFromProduct}
          onAddItemFromService={onAddItemFromService}
          quote={quote}
          setQuote={setQuote}
          setRentalCandidate={setRentalCandidate}
          setRentalDialogOpen={setRentalDialogOpen}
        />

        <AdminQuoteItemsTable
          items={quote.items}
          products={products}
          onUpdateItem={onUpdateItem}
          onRemoveItem={onRemoveItem}
        />
      </section>
    </div>

    <div className="space-y-6"><AdminQuoteSettingsSummary quote={quote} setQuote={setQuote} total={total} /></div>
  </div>
);
