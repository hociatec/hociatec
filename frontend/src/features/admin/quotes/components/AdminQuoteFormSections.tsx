import { type Dispatch, type SetStateAction } from 'react';

import { AdminQuoteItemsTable } from '@/features/admin/quotes/components/AdminQuoteItemsTable';
import { type CatalogProduct } from '@/features/catalog/api';
import type {
  QuoteDto,
  QuoteInput,
  QuoteServiceDto,
  QuoteStatus,
} from '@/features/quotes/types/quoteTypes';
import { type QuoteItem } from '@/features/quotes/utils/quoteFormUtils';
import { formatEuroCents } from '@/shared/lib/formatters';
import { AdminQuoteCustomerFields, AdminQuoteSettingsSummary } from './AdminQuoteCustomerAndSettings';

export type AdminQuoteFormState = {
  id?: number;
  number?: string;
  status: QuoteStatus;
  statusCode?: QuoteStatus;
  statusLabel?: string;
  customer: NonNullable<QuoteInput['customer']>;
  items: QuoteItem[];
  discountCents: number;
  shippingCents: number;
  conditions: string | null;
  validFrom: string | null;
  validUntil: string | null;
  totals?: QuoteDto['totals'];
  createdAt?: string;
  updatedAt?: string;
  sentAt?: string | null;
  convertedOrder?: QuoteDto['convertedOrder'];
  emailNotificationSent?: boolean;
  emailNotificationError?: string | null;
};

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
        <div className="space-y-6">
          {filteredServices.length > 0 && (
            <div>
              <h2 className="text-lg font-semibold mb-2">Services ({filteredServices.length})</h2>
              <div className="space-y-2 max-h-64 overflow-auto">
                {filteredServices.map((service) => (
                  <div key={service.id} className="rounded border border-brand-100 p-2">
                    <div className="flex items-center justify-between">
                      <div>
                        <div className="text-sm font-semibold">{service.title}</div>
                        <div className="text-xs text-stone-500">
                          {service.priceCents != null ? formatEuroCents(service.priceCents) : ''}
                        </div>
                      </div>
                      {quote.items.some(
                        (item) => item.type === 'service' && item.serviceId === service.id,
                      ) ? (
                        <button
                          type="button"
                          className="catalog-admin-actions__delete"
                          onClick={() =>
                            setQuote((current) =>
                              current
                                ? {
                                    ...current,
                                    items: current.items.filter(
                                      (item) =>
                                        !(item.type === 'service' && item.serviceId === service.id),
                                    ),
                                  }
                                : current,
                            )
                          }
                        >
                          Retirer
                        </button>
                      ) : (
                        <button
                          type="button"
                          className="catalog-admin-actions__edit"
                          onClick={() => onAddItemFromService(service.id)}
                        >
                          Ajouter
                        </button>
                      )}
                    </div>
                  </div>
                ))}
              </div>
            </div>
          )}

          {filteredProducts.length > 0 && (
            <div>
              <h2 className="text-lg font-semibold mb-2">Produits ({filteredProducts.length})</h2>
              <div className="space-y-2 max-h-64 overflow-auto">
                {filteredProducts.map((product) => (
                  <div key={product.id} className="rounded border border-brand-100 p-2">
                    <div className="flex items-center justify-between">
                      <div>
                        <div className="text-sm font-semibold">{product.name}</div>
                        <div className="text-xs text-stone-500">Référence: {product.sku}</div>
                        <div className="text-xs text-stone-500">
                          {formatEuroCents(product.effectivePriceCents ?? product.priceCents)}
                          {product.sellingType === 'rental' ? ' / mois' : ''}
                        </div>
                      </div>
                      {product.sellingType === 'rental' ? (
                        <button
                          type="button"
                          className="catalog-admin-actions__edit"
                          onClick={() => {
                            const exists = quote.items.some(
                              (item) => item.type === 'product' && item.productId === product.id,
                            );
                            if (exists) {
                              setRentalCandidate(product);
                              setRentalDialogOpen(true);
                            } else {
                              onAddItemFromProduct(product.id);
                            }
                          }}
                        >
                          Ajouter
                        </button>
                      ) : quote.items.some(
                          (item) => item.type === 'product' && item.productId === product.id,
                        ) ? (
                        <button
                          type="button"
                          className="catalog-admin-actions__delete"
                          onClick={() =>
                            setQuote((current) =>
                              current
                                ? {
                                    ...current,
                                    items: current.items.filter(
                                      (item) =>
                                        !(item.type === 'product' && item.productId === product.id),
                                    ),
                                  }
                                : current,
                            )
                          }
                        >
                          Retirer
                        </button>
                      ) : (
                        <button
                          type="button"
                          className="catalog-admin-actions__edit"
                          onClick={() => onAddItemFromProduct(product.id)}
                        >
                          Ajouter
                        </button>
                      )}
                    </div>
                  </div>
                ))}
              </div>
            </div>
          )}
        </div>

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
