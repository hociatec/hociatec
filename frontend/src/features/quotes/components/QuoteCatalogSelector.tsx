import type { CatalogProduct } from '@/features/catalog/publicApi';
import type { QuoteDraft } from '@/features/quotes/hooks/useCreateQuote';
import type { QuoteServiceDto } from '@/features/quotes/types/quoteTypes';
import { QuoteProductResults } from './QuoteProductResults';
import { QuoteServiceSuggestions } from './QuoteServiceSuggestions';

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
      <QuoteServiceSuggestions
        form={form}
        filteredServices={filteredServices}
        searchQuery={searchQuery}
        addServiceLine={addServiceLine}
        toggleServiceLine={toggleServiceLine}
      />
      <QuoteProductResults
        form={form}
        products={products}
        addProductLineFromProduct={addProductLineFromProduct}
        findProductItemIndex={findProductItemIndex}
        removeItem={removeItem}
        updateItem={updateItem}
        setRentalCandidate={setRentalCandidate}
        setRentalDialogOpen={setRentalDialogOpen}
      />
    </div>
  </section>
);
