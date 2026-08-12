import { formatQuotePrice, type QuoteItem } from '@/features/quotes/publicApi';
import type { CatalogProduct } from '@/features/catalog/adminApi';
import { formatEuroInputFromCents, parseEuroInputToCents } from '@/shared/lib/formatters';
import { parseNonNegativeInteger, parseNullablePositiveInteger, parseNullableNonNegativeDecimal } from '@/shared/lib/parsers';
import { clampAtLeast, clampWithin } from '@/shared/lib/number';

type AdminQuoteItemRowProps = {
  item: QuoteItem;
  index: number;
  products: CatalogProduct[];
  onUpdateItem: (index: number, patch: Partial<QuoteItem>) => void;
  onRemoveItem: (index: number) => void;
};

const parseRate = (value: string) => {
  return parseNullableNonNegativeDecimal(value, 0) ?? 0;
};

export const AdminQuoteItemRow = ({
  item,
  index,
  products: _products,
  onUpdateItem,
  onRemoveItem,
}: AdminQuoteItemRowProps) => {
  const isRental = item.type === 'product' && item.sellingType === 'rental';
  const isCustom = item.type === 'custom';
  const months = isRental ? clampAtLeast(item.rentalMonths ?? 1, 1) : 1;
  const line = clampAtLeast(
    item.unitPriceCents * item.quantity * months - (item.discountCents ?? 0),
    0,
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
                onUpdateItem(index, { quantity: clampWithin(next, 0, 9999) });
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
                const value = parseNonNegativeInteger(event.target.value, 0);
                if (Number.isNaN(value) || value <= 0) {
                  onRemoveItem(index);
                  return;
                }
                onUpdateItem(index, { quantity: clampWithin(value, 0, 9999) });
              }}
              className="quote-stepper-input"
            />
            <button
              type="button"
              className="catalog-admin-actions__edit"
              onClick={() =>
                onUpdateItem(index, { quantity: clampWithin((item.quantity ?? 1) + 1, 0, 9999) })
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
                    rentalMonths: clampAtLeast((item.rentalMonths ?? 1) - 1, 1),
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
                value={clampAtLeast(item.rentalMonths ?? 1, 1)}
                onChange={(event) => {
                  const value = parseNullablePositiveInteger(event.target.value) ?? 1;
                  onUpdateItem(index, {
                    rentalMonths: clampWithin(value, 1, 120),
                  });
                }}
                className="quote-stepper-input"
              />
              <button
                type="button"
                className="catalog-admin-actions__edit"
                onClick={() =>
                  onUpdateItem(index, {
                    rentalMonths: clampWithin((item.rentalMonths ?? 1) + 1, 1, 120),
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
            value={formatEuroInputFromCents(item.unitPriceCents)}
            onChange={(event) =>
              onUpdateItem(index, { unitPriceCents: parseEuroInputToCents(event.target.value) })
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
            value={formatEuroInputFromCents(item.discountCents ?? 0)}
            onChange={(event) =>
              onUpdateItem(index, { discountCents: parseEuroInputToCents(event.target.value) })
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
