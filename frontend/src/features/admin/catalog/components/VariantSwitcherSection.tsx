import type { CatalogProduct } from '@/features/catalog/api';

export const VariantSwitcherSection = ({ currentProductId, deletingVariantId, groupVariants, formatVariantDetails, onDeleteVariant, onNavigateVariant }: {
  currentProductId: number | null;
  deletingVariantId: number | null;
  groupVariants: CatalogProduct[];
  formatVariantDetails: (product: CatalogProduct) => string;
  onDeleteVariant: (variant: CatalogProduct) => void;
  onNavigateVariant: (variantId: number) => void;
}) => (
  <section className="catalog-form-section catalog-variant-switcher">
    <div className="catalog-form-section__header"><h2 className="catalog-form-section__title">Variantes enregistrées</h2><p className="catalog-form-section__description">Chaque variante est un produit distinct. Choisissez celle que vous voulez modifier.</p></div>
    <div className="catalog-variant-switcher__list">{groupVariants.map((variant, index) => { const isActive = variant.id === currentProductId; return <div key={variant.id} className={`catalog-variant-switcher__card${isActive ? ' is-active' : ''}`}><div className="catalog-variant-switcher__item"><span className="catalog-variant-switcher__eyebrow">Variante {variant.variantPosition ?? index + 1}</span><span className="catalog-variant-switcher__name">{formatVariantDetails(variant)}</span><span className="catalog-variant-switcher__meta">SKU : {variant.sku} · Stock : {variant.stock}</span></div><button type="button" className="catalog-variant-switcher__select" disabled={isActive} onClick={() => onNavigateVariant(variant.id)}>{isActive ? 'Variante active' : 'Modifier cette variante'}</button><button type="button" className="catalog-variant-switcher__remove" disabled={deletingVariantId !== null} onClick={() => onDeleteVariant(variant)}>{deletingVariantId === variant.id ? 'Suppression...' : 'Retirer la variante'}</button></div>; })}</div>
  </section>
);
