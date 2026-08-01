import type { CatalogProduct } from '@/features/catalog/api';
import type { QuoteDraft } from '@/features/quotes/hooks/useCreateQuote';
import { formatEuroCents } from '@/shared/lib/formatters';
import { QuoteQuantityControl } from './QuoteQuantityControl';

type QuoteProductResultsProps = {
  form: QuoteDraft;
  products: CatalogProduct[];
  addProductLineFromProduct: (product: CatalogProduct) => void;
  findProductItemIndex: (productId: number) => number;
  removeItem: (index: number) => void;
  updateItem: (index: number, patch: { quantity: number }) => void;
  setRentalCandidate: (product: CatalogProduct | null) => void;
  setRentalDialogOpen: (open: boolean) => void;
};

export const QuoteProductResults = ({
  form,
  products,
  addProductLineFromProduct,
  findProductItemIndex,
  removeItem,
  updateItem,
  setRentalCandidate,
  setRentalDialogOpen,
}: QuoteProductResultsProps) => {
  if (products.length === 0) {
    return null;
  }

  return (
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
                <ProductAction
                  form={form}
                  product={product}
                  selectedIndex={selectedIndex}
                  addProductLineFromProduct={addProductLineFromProduct}
                  removeItem={removeItem}
                  updateItem={updateItem}
                  setRentalCandidate={setRentalCandidate}
                  setRentalDialogOpen={setRentalDialogOpen}
                />
              </div>
            </div>
          );
        })}
      </div>
    </div>
  );
};

const ProductAction = ({
  form,
  product,
  selectedIndex,
  addProductLineFromProduct,
  removeItem,
  updateItem,
  setRentalCandidate,
  setRentalDialogOpen,
}: {
  form: QuoteDraft;
  product: CatalogProduct;
  selectedIndex: number;
  addProductLineFromProduct: (product: CatalogProduct) => void;
  removeItem: (index: number) => void;
  updateItem: (index: number, patch: { quantity: number }) => void;
  setRentalCandidate: (product: CatalogProduct | null) => void;
  setRentalDialogOpen: (open: boolean) => void;
}) => {
  if (product.sellingType === 'rental') {
    return (
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
    );
  }

  if (selectedIndex < 0) {
    return (
      <button
        type="button"
        className="register-form__submit quote-builder__small-button"
        onClick={() => addProductLineFromProduct(product)}
      >
        Ajouter
      </button>
    );
  }

  const currentQuantity = Math.max(1, form.items[selectedIndex]?.quantity ?? 1);

  return (
    <QuoteQuantityControl
      productName={product.name}
      quantity={currentQuantity}
      onDecrease={() => {
        if (currentQuantity <= 1) removeItem(selectedIndex);
        else updateItem(selectedIndex, { quantity: currentQuantity - 1 });
      }}
      onIncrease={() => updateItem(selectedIndex, { quantity: currentQuantity + 1 })}
      onChange={(nextQuantity) => {
        if (Number.isNaN(nextQuantity) || nextQuantity <= 0) removeItem(selectedIndex);
        else updateItem(selectedIndex, { quantity: nextQuantity });
      }}
    />
  );
};
