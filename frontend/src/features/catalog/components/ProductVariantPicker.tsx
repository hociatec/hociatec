import { useId, type KeyboardEvent } from 'react';

import type { ProductVariantGroup } from './productVariantTypes';

interface ProductVariantPickerProps {
  currentProductId: number;
  groups: ProductVariantGroup[];
  onVariantChange: (variantId: string) => void;
}

export const ProductVariantPicker = ({
  currentProductId,
  groups,
  onVariantChange,
}: ProductVariantPickerProps) => {
  const titleId = useId();
  const flatVariants = groups.flatMap((group) => group.items);
  const selectedIndex = Math.max(
    0,
    flatVariants.findIndex((variant) => variant.id === currentProductId),
  );

  const moveSelection = (nextIndex: number) => {
    const nextVariant = flatVariants[nextIndex];
    if (nextVariant && nextVariant.id !== currentProductId) {
      onVariantChange(String(nextVariant.id));
    }
  };

  const handleKeyDown = (event: KeyboardEvent<HTMLButtonElement>, variantId: number) => {
    const currentIndex = flatVariants.findIndex((variant) => variant.id === variantId);
    if (currentIndex < 0) return;

    switch (event.key) {
      case 'ArrowRight':
      case 'ArrowDown':
        event.preventDefault();
        moveSelection((currentIndex + 1) % flatVariants.length);
        break;
      case 'ArrowLeft':
      case 'ArrowUp':
        event.preventDefault();
        moveSelection((currentIndex - 1 + flatVariants.length) % flatVariants.length);
        break;
      case 'Home':
        event.preventDefault();
        moveSelection(0);
        break;
      case 'End':
        event.preventDefault();
        moveSelection(flatVariants.length - 1);
        break;
      default:
        break;
    }
  };

  return (
    <div className="catalog-detail-variant-picker">
      <h2 id={titleId}>Choisir une variante</h2>
      <p className="catalog-detail-variant-picker__hint">
        Choisissez une variante parmi celles affichées ci-dessous. La carte active correspond à
        la variante actuellement affichée.
      </p>
      <div
        className="catalog-detail-variant-groups"
        role="radiogroup"
        aria-labelledby={titleId}
      >
        {groups.map((group) => (
          <section key={group.label} className="catalog-detail-variant-group">
            <h3 className="catalog-detail-variant-group__title">{group.label}</h3>
            <div className="catalog-detail-variant-picker__grid">
              {group.items.map((variant) => {
                const isSelected = variant.id === currentProductId;
                const variantIndex = flatVariants.findIndex((item) => item.id === variant.id);

                return (
                  <button
                    key={variant.id}
                    type="button"
                    role="radio"
                    className={`catalog-detail-variant-card${isSelected ? ' is-active' : ''}`}
                    aria-checked={isSelected}
                    aria-label={variant.accessibilityLabel}
                    tabIndex={variantIndex === selectedIndex ? 0 : -1}
                    onClick={() => onVariantChange(String(variant.id))}
                    onKeyDown={(event) => handleKeyDown(event, variant.id)}
                  >
                    <span className="catalog-detail-variant-card__title">{variant.title}</span>
                    <span className="catalog-detail-variant-card__meta">{variant.subtitle}</span>
                    <span className="catalog-detail-variant-card__footer">
                      <span className="catalog-detail-variant-card__price">
                        Prix : {variant.priceLabel}
                      </span>
                    </span>
                    <span className="catalog-detail-variant-card__action" aria-hidden="true">
                      {isSelected
                        ? 'Variante actuelle'
                        : variant.isAvailable
                          ? 'Choisir cette variante'
                          : 'Variante indisponible'}
                    </span>
                  </button>
                );
              })}
            </div>
          </section>
        ))}
      </div>
    </div>
  );
};
