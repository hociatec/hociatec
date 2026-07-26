import type { CatalogProduct } from '@/features/catalog/api';
import type { QuoteDraft } from '@/features/quotes/hooks/useCreateQuote';
import type { QuoteServiceDto } from '@/features/quotes/types/quoteTypes';
import { formatEuroCents } from '@/shared/lib/formatters';

type QuoteCatalogSelectorProps = {
  form: QuoteDraft;
  filteredServices: QuoteServiceDto[];
  products: CatalogProduct[];
  productLoading: boolean;
  searchQuery: string;
  setSearchQuery: (value: string) => void;
  addProductLineFromProduct: (product: CatalogProduct) => void;
  addServiceLine: (serviceId: number) => void;
  toggleServiceLine: (serviceId: number) => void;
  findProductItemIndex: (productId: number) => number;
  removeItem: (index: number) => void;
  updateItem: (index: number, patch: { quantity: number }) => void;
  setRentalCandidate: (product: CatalogProduct | null) => void;
  setRentalDialogOpen: (open: boolean) => void;
};

export const QuoteCatalogSelector = ({
  form,
  filteredServices,
  products,
  productLoading,
  searchQuery,
  setSearchQuery,
  addProductLineFromProduct,
  addServiceLine,
  toggleServiceLine,
  findProductItemIndex,
  removeItem,
  updateItem,
  setRentalCandidate,
  setRentalDialogOpen,
}: QuoteCatalogSelectorProps) => (
  <section>
    <h3 className="font-semibold mb-2">Produits et services à chiffrer</h3>
    <div className="mb-4">
      <label className="register-form__field">
        <span>Rechercher dans le catalogue</span>
        <input
          type="search"
          placeholder="Tapez au moins 2 caractères: iPhone, audit, formation..."
          value={searchQuery}
          onChange={(event) => setSearchQuery(event.target.value)}
        />
      </label>
    </div>
    {productLoading && (
      <p className="text-sm text-stone-500">Recherche des produits et services correspondants...</p>
    )}
    <div className="space-y-6">
      {searchQuery.trim().length >= 2 && filteredServices.length > 0 && (
        <div>
          <h2 className="text-lg font-semibold mb-2">Services suggérés ({filteredServices.length})</h2>
          <div className="space-y-2 max-h-64 overflow-auto">
            {filteredServices.map((service) => {
              const selected = (form.items ?? []).some(
                (item) => item.type === 'service' && item.serviceId === service.id,
              );
              return (
                <div key={service.id} className="rounded border border-brand-100 p-2">
                  <div className="flex items-center justify-between">
                    <div>
                      <div className="text-sm font-semibold">{service.title}</div>
                      <div className="text-xs text-stone-500">
                        {formatEuroCents(service.priceCents ?? 0)}
                      </div>
                    </div>
                    <button
                      type="button"
                      className={selected ? 'catalog-admin-actions__delete' : 'register-form__submit quote-builder__small-button'}
                      onClick={() => (selected ? toggleServiceLine(service.id) : addServiceLine(service.id))}
                    >
                      {selected ? 'Retirer' : 'Ajouter'}
                    </button>
                  </div>
                </div>
              );
            })}
          </div>
        </div>
      )}
      {products.length > 0 && (
        <div>
          <h2 className="text-lg font-semibold mb-2">
            Produits disponibles ({Math.min(products.length, 20)})
          </h2>
          <div className="space-y-2 max-h-64 overflow-auto">
            {products.slice(0, 20).map((product) => {
              const selectedIndex = findProductItemIndex(product.id);
              return (
                <div key={product.id} className="rounded border border-brand-100 p-2">
                  <div className="flex items-center justify-between gap-3">
                    <div>
                      <div className="text-sm font-semibold">{product.name}</div>
                      <div className="text-xs text-stone-500">Référence: {product.sku}</div>
                      <div className="text-xs text-stone-500">
                        {formatEuroCents(product.effectivePriceCents ?? product.priceCents ?? 0)}
                        {product.sellingType === 'rental' ? ' / mois' : ''}
                      </div>
                    </div>
                    {product.sellingType === 'rental' ? (
                      <button
                        type="button"
                        className="register-form__submit quote-builder__small-button"
                        onClick={() => {
                          if (selectedIndex >= 0) {
                            setRentalCandidate(product);
                            setRentalDialogOpen(true);
                          } else {
                            addProductLineFromProduct(product);
                          }
                        }}
                      >
                        Ajouter
                      </button>
                    ) : selectedIndex >= 0 ? (
                      <div className="inline-flex items-center gap-2">
                        <button
                          type="button"
                          className="px-2 py-1 border rounded"
                          aria-label={`Diminuer la quantité de ${product.name}`}
                          onClick={() => {
                            const currentQuantity = Math.max(1, form.items[selectedIndex]?.quantity ?? 1);
                            if (currentQuantity <= 1) removeItem(selectedIndex);
                            else updateItem(selectedIndex, { quantity: currentQuantity - 1 });
                          }}
                        >
                          -
                        </button>
                        <input
                          type="number"
                          min={0}
                          className="w-16 text-center border rounded py-1"
                          aria-label={`Quantité de ${product.name}`}
                          value={Math.max(1, form.items[selectedIndex]?.quantity ?? 1)}
                          onChange={(event) => {
                            const nextQuantity = Number.parseInt(event.target.value, 10);
                            if (Number.isNaN(nextQuantity) || nextQuantity <= 0) removeItem(selectedIndex);
                            else updateItem(selectedIndex, { quantity: nextQuantity });
                          }}
                        />
                        <button
                          type="button"
                          className="px-2 py-1 border rounded"
                          aria-label={`Augmenter la quantité de ${product.name}`}
                          onClick={() => {
                            const currentQuantity = Math.max(1, form.items[selectedIndex]?.quantity ?? 1);
                            updateItem(selectedIndex, { quantity: currentQuantity + 1 });
                          }}
                        >
                          +
                        </button>
                      </div>
                    ) : (
                      <button
                        type="button"
                        className="register-form__submit quote-builder__small-button"
                        onClick={() => addProductLineFromProduct(product)}
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
  </section>
);
