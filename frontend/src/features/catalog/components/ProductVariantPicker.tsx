import type { ProductVariantGroup } from './productVariantTypes';

export const ProductVariantPicker = ({ currentProductId, groups, onVariantChange }: { currentProductId: number; groups: ProductVariantGroup[]; onVariantChange: (variantId: string) => void }) => (
  <div className="catalog-detail-variant-picker">
    <h2>Choisir une variante</h2>
    <div className="catalog-detail-variant-groups" aria-label="Variantes du produit">
      {groups.map((group) => <section key={group.storage} className="catalog-detail-variant-group"><h3 className="catalog-detail-variant-group__title">Stockage : {group.storage}</h3><div className="catalog-detail-variant-picker__grid" role="list">{group.items.map((variant) => <button key={variant.id} type="button" className={`catalog-detail-variant-card${variant.id === currentProductId ? ' is-active' : ''}`} onClick={() => onVariantChange(String(variant.id))} aria-pressed={variant.id === currentProductId} aria-label={`Choisir la variante. ${variant.color ? `Couleur : ${variant.color}. ` : ''}${variant.storage ? `Stockage : ${variant.storage}. ` : ''}Prix : ${variant.priceLabel}. ${variant.stockLabel}.`}><span className="catalog-detail-variant-card__title">{variant.color ? `Couleur : ${variant.color}` : variant.title}</span><span className="catalog-detail-variant-card__meta">{variant.subtitle}</span><span className="catalog-detail-variant-card__footer"><span className="catalog-detail-variant-card__price">Prix : {variant.priceLabel}</span><span className="catalog-detail-variant-card__stock">{variant.stockLabel}</span></span></button>)}</div></section>)}
    </div>
  </div>
);
