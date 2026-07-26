import type { CatalogProduct, CatalogSearchMeta } from '../apiTypes';
import { getCatalogPageNumbers } from '../lib/catalogSearch';
import { ProductActionToolbar } from './ProductActionToolbar';
import { ProductCard } from './ProductCard';
import { FeedbackMessage, LoadingState } from '@/shared/components/ui/page-state';

interface CatalogProductResultsProps { products: CatalogProduct[]; meta: CatalogSearchMeta; loading: boolean; error: string | null; emptyMessage: string; loadingMessage: string; onPageChange: (page: number) => void; }
export const CatalogProductResults = ({ products, meta, loading, error, emptyMessage, loadingMessage, onPageChange }: CatalogProductResultsProps) => <>
  {loading && <LoadingState>{loadingMessage}</LoadingState>}
  {error && <FeedbackMessage>{error}</FeedbackMessage>}
  {!loading && !error && <>
    <section className="catalog-grid catalog-grid--products">{products.length === 0 ? <div className="catalog-empty-state">{emptyMessage}</div> : products.map((product) => <ProductCard key={product.id} product={product} actionSlot={<ProductActionToolbar product={product} />} />)}</section>
    {meta.totalPages > 1 && <nav className="catalog-pagination" aria-label="Pagination des produits">
      <button type="button" className="catalog-pagination__button" disabled={meta.page <= 1} onClick={() => onPageChange(meta.page - 1)}>Précédent</button>
      {getCatalogPageNumbers(meta).map((page) => <button key={page} type="button" className={`catalog-pagination__button${page === meta.page ? ' is-active' : ''}`} onClick={() => onPageChange(page)} aria-current={page === meta.page ? 'page' : undefined}>{page}</button>)}
      <button type="button" className="catalog-pagination__button" disabled={meta.page >= meta.totalPages} onClick={() => onPageChange(meta.page + 1)}>Suivant</button>
    </nav>}
  </>}
</>;
