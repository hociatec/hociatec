import { AdminProductsFilters } from '../components/AdminProductsFilters';
import { AdminProductsTable } from '../components/AdminProductsTable';
import { useAdminProductsList } from '../hooks/useAdminProductsList';
import { PageContainer } from '@/shared/components/layout/PageContainer';
import { useDocumentTitle } from '@/shared/hooks/useDocumentTitle';
import { PaginationControls } from '@/shared/components/ui/PaginationControls';
import { FeedbackMessage, PrimaryLink } from '@/shared/components/ui/page-state';

import '@/features/catalog/pages/CatalogPages.css';

export const ProductsListPage = () => {
  useDocumentTitle('Admin - Produits');
  const controller = useAdminProductsList();
  const total = controller.meta?.total ?? controller.filteredProducts.length;

  return (
    <PageContainer
      size="admin"
      title="Produits"
      headerActions={<PrimaryLink to="/admin/catalog/products/new">Nouveau produit</PrimaryLink>}
    >
      <div className="mb-6 space-y-1">
        <p className="text-sm text-stone-600">
          {total} produit{total > 1 ? 's' : ''} trouvé{total > 1 ? 's' : ''}
        </p>
        <p className="text-sm text-stone-500">
          Recherchez un produit, contrôlez les stocks et ouvrez rapidement les fiches à corriger.
        </p>
      </div>

      {controller.stockFilter === 'low' && (
        <div className="mb-6 rounded-2xl border border-amber-200 bg-amber-50 px-5 py-4 text-sm text-amber-900">
          <div className="font-semibold">Stock faible à traiter</div>
          <div className="mt-1">
            Cette vue affiche les produits dont le stock total est inférieur ou égal à 3. Ouvrez la
            fiche produit pour ajuster le stock ou préparer le réassort.
          </div>
        </div>
      )}

      <AdminProductsFilters
        categories={controller.categories}
        featuredFilter={controller.featuredFilter}
        filterCategory={controller.filterCategory}
        hasActiveFilters={controller.hasActiveFilters}
        maxPrice={controller.maxPrice}
        minPrice={controller.minPrice}
        search={controller.search}
        sellingTypeFilter={controller.sellingTypeFilter}
        sort={controller.sort}
        stockFilter={controller.stockFilter}
        onFeaturedFilterChange={controller.setFeaturedFilter}
        onFilterCategoryChange={controller.setFilterCategory}
        onPriceRangeChange={controller.setPriceRange}
        onReset={controller.resetFilters}
        onSearchChange={controller.setSearch}
        onSellingTypeFilterChange={controller.setSellingTypeFilter}
        onSortChange={controller.setSort}
        onStockFilterChange={controller.setStockFilter}
      />

      {controller.error && <FeedbackMessage>{controller.error}</FeedbackMessage>}
      {controller.message && <FeedbackMessage variant="success">{controller.message}</FeedbackMessage>}

      <AdminProductsTable
        loading={controller.loading}
        products={controller.filteredProducts}
        onDelete={(id) => void controller.handleDelete(id)}
      />

      <PaginationControls
        page={controller.page}
        total={controller.meta?.total}
        totalLabel="produit"
        totalPages={controller.meta?.totalPages ?? 1}
        onPageChange={controller.setPage}
      />
    </PageContainer>
  );
};
