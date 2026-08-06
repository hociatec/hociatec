import { formatQuotePrice, type QuoteItem } from '@/features/quotes/utils/quoteFormUtils';
import { parseNullablePositiveInteger } from '@/shared/lib/parsers';
import { clampAtLeast } from '@/shared/lib/number';

import './QuoteComponents.css';

type PublicQuoteSelectionListProps = {
  items: QuoteItem[];
  onUpdateItem: (index: number, patch: Partial<QuoteItem>) => void;
  onRemoveItem: (index: number) => void;
};

export const PublicQuoteSelectionList = ({
  items,
  onUpdateItem,
  onRemoveItem,
}: PublicQuoteSelectionListProps) => {
  if (items.length === 0) {
    return (
      <div className="quote-selection-empty">
        Aucun élément ajouté. Recherchez un produit ou un service pour commencer votre devis.
      </div>
    );
  }

  return (
    <div className="quote-selection-list">
      {items.map((item, index) => {
        const isRental = item.type === 'product' && Boolean(item.rentalMonths);
        const months = isRental ? clampAtLeast(item.rentalMonths ?? 1, 1) : 1;
        const line = clampAtLeast(
          (item.unitPriceCents ?? 0) * (item.quantity ?? 1) * months - (item.discountCents ?? 0),
          0,
        );

        return (
          <article
            key={`${item.type}-${item.productId ?? item.serviceId ?? index}`}
            className="quote-selection-card"
          >
            <div className="quote-selection-card__header">
              <div className="quote-selection-card__title-area">
                <div className="quote-selection-card__title-row">
                  <h4 className="quote-selection-card__title">{item.name}</h4>
                  <span className="quote-selection-card__badge">
                    {item.type === 'service' ? 'Service' : isRental ? 'Location' : 'Produit'}
                  </span>
                </div>
                <p className="quote-selection-card__meta">
                  {formatQuotePrice(item.unitPriceCents)}
                  {isRental ? ' / mois' : ''}
                  {' · TVA '}
                  {(item.vatRate ?? 0).toString()}%
                  {(item.discountCents ?? 0) > 0
                    ? ` · Remise ${formatQuotePrice(item.discountCents ?? 0)}`
                    : ''}
                </p>
              </div>

              <div className="quote-selection-card__subtotal">
                <p className="quote-selection-card__subtotal-label">Sous-total HT</p>
                <p className="quote-selection-card__subtotal-value">{formatQuotePrice(line)}</p>
              </div>
            </div>

            <div className="quote-selection-card__controls">
              <div className="quote-selection-card__steppers">
                <div className="quote-selection-stepper">
                  <span className="quote-selection-stepper__label">Quantité</span>
                  <button
                    type="button"
                    aria-label="Diminuer la quantité"
                    className="quote-selection-stepper__button"
                    onClick={() =>
                      onUpdateItem(index, { quantity: clampAtLeast((item.quantity ?? 1) - 1, 1) })
                    }
                  >
                    -
                  </button>
                  <input
                    type="number"
                    min={1}
                    className="quote-selection-stepper__input"
                    value={item.quantity ?? 1}
                    onChange={(event) => {
                      const value = parseNullablePositiveInteger(event.target.value) ?? 1;
                      onUpdateItem(index, {
                        quantity: clampAtLeast(value, 1),
                      });
                    }}
                  />
                  <button
                    type="button"
                    aria-label="Augmenter la quantité"
                    className="quote-selection-stepper__button"
                    onClick={() =>
                      onUpdateItem(index, { quantity: clampAtLeast((item.quantity ?? 1) + 1, 1) })
                    }
                  >
                    +
                  </button>
                </div>

                {isRental && (
                  <div className="quote-selection-stepper">
                    <span className="quote-selection-stepper__label">Durée</span>
                    <button
                      type="button"
                      aria-label="Diminuer le nombre de mois"
                      className="quote-selection-stepper__button"
                      onClick={() =>
                        onUpdateItem(index, {
                          rentalMonths: clampAtLeast((item.rentalMonths ?? 1) - 1, 1),
                        })
                      }
                    >
                      -
                    </button>
                    <input
                      type="number"
                      min={1}
                      className="quote-selection-stepper__input"
                      value={clampAtLeast(item.rentalMonths ?? 1, 1)}
                      onChange={(event) => {
                        const value = parseNullablePositiveInteger(event.target.value) ?? 1;
                        onUpdateItem(index, {
                          rentalMonths: clampAtLeast(value, 1),
                        });
                      }}
                    />
                    <span className="quote-selection-stepper__unit">mois</span>
                    <button
                      type="button"
                      aria-label="Augmenter le nombre de mois"
                      className="quote-selection-stepper__button"
                      onClick={() =>
                        onUpdateItem(index, {
                          rentalMonths: clampAtLeast((item.rentalMonths ?? 1) + 1, 1),
                        })
                      }
                    >
                      +
                    </button>
                  </div>
                )}
              </div>

              <button
                type="button"
                className="quote-selection-card__remove"
                onClick={() => onRemoveItem(index)}
              >
                Retirer
              </button>
            </div>
          </article>
        );
      })}
    </div>
  );
};
