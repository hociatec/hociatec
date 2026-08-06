import type { CatalogProduct } from '@/features/catalog/adminApi';
import { PaginationControls } from '@/shared/components/ui/PaginationControls';
import { ADMIN_PAGE_SIZE, useAdminPagination } from '@/shared/hooks/useAdminPagination';

export const VariantSwitcherSection = ({ currentProductId, deletingVariantId, groupVariants, formatVariantDetails, onDeleteVariant, onNavigateVariant }: {
  currentProductId: number | null;
  deletingVariantId: number | null;
  groupVariants: CatalogProduct[];
  formatVariantDetails: (product: CatalogProduct) => string;
  onDeleteVariant: (variant: CatalogProduct) => void;
  onNavigateVariant: (variantId: number) => void;
}) => {
  const variantsPagination = useAdminPagination(groupVariants, `variants-${groupVariants.map((variant) => variant.id).join('-')}`);

  return (
    <section className="catalog-form-section catalog-variant-switcher">
      <div className="catalog-form-section__header"><h2 className="catalog-form-section__title">Variantes enregistrées</h2><p className="catalog-form-section__description">Chaque variante est un produit distinct. Choisissez celle que vous voulez modifier.</p></div>
      <div className="catalog-variant-switcher__list">{variantsPagination.paginatedItems.map((variant, visibleIndex) => { const index = (variantsPagination.page - 1) * ADMIN_PAGE_SIZE + visibleIndex; const isActive = variant.id === currentProductId; return <div key={variant.id} className={`catalog-variant-switcher__card${isActive ? ' is-active' : ''}`}><div className="catalog-variant-switcher__item"><span className="catalog-variant-switcher__eyebrow">Variante {variant.variantPosition ?? index + 1}</span><span className="catalog-variant-switcher__name">{formatVariantDetails(variant)}</span><span className="catalog-variant-switcher__meta">SKU : {variant.sku} · Stock : {variant.stock}</span></div><button type="button" className="catalog-variant-switcher__select" disabled={isActive} onClick={() => onNavigateVariant(variant.id)}>{isActive ? 'Variante active' : 'Modifier cette variante'}</button><button type="button" className="catalog-variant-switcher__remove" disabled={deletingVariantId !== null} onClick={() => onDeleteVariant(variant)}>{deletingVariantId === variant.id ? 'Suppression...' : 'Retirer la variante'}</button></div>; })}</div>
      <PaginationControls className="mt-3" page={variantsPagination.page} total={variantsPagination.total} totalLabel="variante" totalPages={variantsPagination.totalPages} onPageChange={variantsPagination.setPage} />
    </section>
  );
};
