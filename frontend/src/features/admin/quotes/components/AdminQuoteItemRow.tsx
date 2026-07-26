import { formatQuotePrice, type QuoteItem } from '@/features/quotes/utils/quoteFormUtils';
import type { CatalogProduct } from '@/features/catalog/api';

type AdminQuoteItemRowProps = {
  item: QuoteItem;
  index: number;
  products: CatalogProduct[];
  onUpdateItem: (index: number, patch: Partial<QuoteItem>) => void;
  onRemoveItem: (index: number) => void;
};

const parseCents = (value: string) => {
  const normalized = value.replace(',', '.');
  const amount = Number.parseFloat(normalized);

  return Number.isFinite(amount) ? Math.max(0, Math.round(amount * 100)) : 0;
};

const parseRate = (value: string) => {
  const normalized = value.replace(',', '.');
  const rate = Number.parseFloat(normalized);

  return Number.isFinite(rate) ? Math.max(0, rate) : 0;
};

export const AdminQuoteItemRow = ({
  item,
  index,
  products,
  onUpdateItem,
  onRemoveItem,
}: AdminQuoteItemRowProps) => {
  const isRental =
    item.type === 'product' &&
    products.some(
      (product) => product.id === item.productId && product.sellingType === 'rental',
    );
  const isCustom = item.type === 'custom';
  const months = isRental ? Math.max(1, item.rentalMonths ?? 1) : 1;
  const line = Math.max(
    0,
    item.unitPriceCents * item.quantity * months - (item.discountCents ?? 0),
  );

  return (
    <tr>
      <td>
        {isCustom ? (
          <input
            type="text"
            value={item.name}
            onChange={(event) => onUpdateItem(index, { name: event.target.value })}
            className="quote-line-input quote-line-input--name"
            aria-label="Nom de la ligne"
          />
        ) : (
          item.name
        )}
        {isRental && <span className="catalog-badge quote-badge-offset">Location</span>}
        {isCustom && <span className="catalog-badge quote-badge-offset">Manuel</span>}
      </td>
      <td>
        {isCustom ? (
          <textarea
            rows={2}
            value={item.description ?? ''}
            onChange={(event) => onUpdateItem(index, { description: event.target.value })}
            className="quote-line-input quote-line-textarea"
            aria-label="Description de la ligne"
          />
        ) : (
          item.description ?? ''
        )}
      </td>
      <td>
        <div className="quote-stepper-stack">
          <div className="quote-stepper-row">
            <button
              type="button"
              className="catalog-admin-actions__edit"
              onClick={() => {
                const next = (item.quantity ?? 1) - 1;
                if (next <= 0) {
                  onRemoveItem(index);
                  return;
                }
                onUpdateItem(index, { quantity: Math.min(9999, next) });
              }}
              aria-label="Diminuer la quantité"
            >
              -
            </button>
            <input
              type="number"
              min={0}
              step={1}
              max={9999}
              value={item.quantity}
              onChange={(event) => {
                const value = Number.parseInt(event.target.value, 10);
                if (Number.isNaN(value) || value <= 0) {
                  onRemoveItem(index);
                  return;
                }
                onUpdateItem(index, { quantity: Math.min(9999, value) });
              }}
              className="quote-stepper-input"
            />
            <button
              type="button"
              className="catalog-admin-actions__edit"
              onClick={() =>
                onUpdateItem(index, { quantity: Math.min(9999, (item.quantity ?? 1) + 1) })
              }
              aria-label="Augmenter la quantité"
            >
              +
            </button>
          </div>

          {isRental && (
            <div className="quote-stepper-row">
              <span className="muted">Mois</span>
              <button
                type="button"
                className="catalog-admin-actions__edit"
                onClick={() =>
                  onUpdateItem(index, {
                    rentalMonths: Math.max(1, (item.rentalMonths ?? 1) - 1),
                  })
                }
                aria-label="Diminuer le nombre de mois"
              >
                -
              </button>
              <input
                type="number"
                min={1}
                step={1}
                max={120}
                value={Math.max(1, item.rentalMonths ?? 1)}
                onChange={(event) => {
                  const value = Number.parseInt(event.target.value, 10);
                  onUpdateItem(index, {
                    rentalMonths: Number.isNaN(value) ? 1 : Math.max(1, Math.min(120, value)),
                  });
                }}
                className="quote-stepper-input"
              />
              <button
                type="button"
                className="catalog-admin-actions__edit"
                onClick={() =>
                  onUpdateItem(index, {
                    rentalMonths: Math.min(120, (item.rentalMonths ?? 1) + 1),
                  })
                }
                aria-label="Augmenter le nombre de mois"
              >
                +
              </button>
            </div>
          )}

          {isCustom && (
            <input
              type="text"
              value={item.unit ?? ''}
              onChange={(event) => onUpdateItem(index, { unit: event.target.value })}
              className="quote-line-input quote-line-input--unit"
              aria-label="Unité"
              placeholder="unité"
            />
          )}
        </div>
      </td>
      <td>
        {isCustom ? (
          <input
            type="number"
            min={0}
            step="0.01"
            value={(item.unitPriceCents / 100).toFixed(2)}
            onChange={(event) =>
              onUpdateItem(index, { unitPriceCents: parseCents(event.target.value) })
            }
            className="quote-line-input quote-line-input--money"
            aria-label="Prix HT"
          />
        ) : (
          <>{formatQuotePrice(item.unitPriceCents)}{isRental ? ' / mois' : ''}</>
        )}
      </td>
      <td>
        {isCustom ? (
          <input
            type="number"
            min={0}
            step="0.1"
            value={item.vatRate ?? 0}
            onChange={(event) => onUpdateItem(index, { vatRate: parseRate(event.target.value) })}
            className="quote-line-input quote-line-input--rate"
            aria-label="TVA en pourcentage"
          />
        ) : (
          `${(item.vatRate ?? 0).toString()}%`
        )}
      </td>
      <td>
        {isCustom ? (
          <input
            type="number"
            min={0}
            step="0.01"
            value={((item.discountCents ?? 0) / 100).toFixed(2)}
            onChange={(event) =>
              onUpdateItem(index, { discountCents: parseCents(event.target.value) })
            }
            className="quote-line-input quote-line-input--money"
            aria-label="Remise"
          />
        ) : (
          formatQuotePrice(item.discountCents ?? 0)
        )}
      </td>
      <td>{formatQuotePrice(line)}</td>
      <td>
        <button
          type="button"
          className="catalog-admin-actions__delete"
          onClick={() => onRemoveItem(index)}
        >
          Retirer
        </button>
      </td>
    </tr>
  );
};
