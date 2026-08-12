import type { QuoteItem } from '@/features/quotes/publicApi';
import type { CatalogProduct } from '@/features/catalog/adminApi';
import { AdminQuoteItemRow } from './AdminQuoteItemRow';

type AdminQuoteItemsTableProps = {
  items: QuoteItem[];
  products: CatalogProduct[];
  onUpdateItem: (index: number, patch: Partial<QuoteItem>) => void;
  onRemoveItem: (index: number) => void;
};

export const AdminQuoteItemsTable = ({
  items,
  products,
  onUpdateItem,
  onRemoveItem,
}: AdminQuoteItemsTableProps) => {
  return (
    <div>
      <div className="quote-table-scroll">
        <table className="catalog-admin-table">
          <thead>
            <tr>
              <th>Nom</th>
              <th>Description</th>
              <th>Quantité</th>
              <th>Prix HT</th>
              <th>TVA %</th>
              <th>Remise</th>
              <th>Total</th>
              <th></th>
            </tr>
          </thead>
          <tbody>
            {items.map((item, index) => {
              return (
                <AdminQuoteItemRow
                  key={`${item.type}-${item.productId ?? item.serviceId ?? index}`}
                  item={item}
                  index={index}
                  products={products}
                  onUpdateItem={onUpdateItem}
                  onRemoveItem={onRemoveItem}
                />
              );
            })}
          </tbody>
        </table>
      </div>
    </div>
  );
};
