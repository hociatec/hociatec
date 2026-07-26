import type { Dispatch, SetStateAction } from 'react';

import type { CatalogProduct } from '@/features/catalog/api';
import type { QuoteServiceDto } from '@/features/quotes/types/quoteTypes';
import { formatEuroCents } from '@/shared/lib/formatters';
import type { AdminQuoteFormState } from './AdminQuoteFormSections';

type AdminQuoteCatalogSearchResultsProps = {
  filteredProducts: CatalogProduct[];
  filteredServices: QuoteServiceDto[];
  onAddItemFromProduct: (productId: number) => void;
  onAddItemFromService: (serviceId: number) => void;
  quote: AdminQuoteFormState;
  setQuote: Dispatch<SetStateAction<AdminQuoteFormState | null>>;
  setRentalCandidate: (product: CatalogProduct) => void;
  setRentalDialogOpen: (open: boolean) => void;
};

export const AdminQuoteCatalogSearchResults = ({
  filteredProducts,
  filteredServices,
  onAddItemFromProduct,
  onAddItemFromService,
  quote,
  setQuote,
  setRentalCandidate,
  setRentalDialogOpen,
}: AdminQuoteCatalogSearchResultsProps) => (
  <div className="space-y-6">
    {filteredServices.length > 0 && (
      <div>
        <h2 className="mb-2 text-lg font-semibold">Services ({filteredServices.length})</h2>
        <div className="max-h-64 space-y-2 overflow-auto">
          {filteredServices.map((service) => {
            const isAdded = quote.items.some(
              (item) => item.type === 'service' && item.serviceId === service.id,
            );

            return (
              <div key={service.id} className="rounded border border-brand-100 p-2">
                <div className="flex items-center justify-between">
                  <div>
                    <div className="text-sm font-semibold">{service.title}</div>
                    <div className="text-xs text-stone-500">
                      {service.priceCents != null ? formatEuroCents(service.priceCents) : ''}
                    </div>
                  </div>
                  {isAdded ? (
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
            );
          })}
        </div>
      </div>
    )}

    {filteredProducts.length > 0 && (
      <div>
        <h2 className="mb-2 text-lg font-semibold">Produits ({filteredProducts.length})</h2>
        <div className="max-h-64 space-y-2 overflow-auto">
          {filteredProducts.map((product) => {
            const isAdded = quote.items.some(
              (item) => item.type === 'product' && item.productId === product.id,
            );

            return (
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
                        if (isAdded) {
                          setRentalCandidate(product);
                          setRentalDialogOpen(true);
                        } else {
                          onAddItemFromProduct(product.id);
                        }
                      }}
                    >
                      Ajouter
                    </button>
                  ) : isAdded ? (
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
            );
          })}
        </div>
      </div>
    )}
  </div>
);
