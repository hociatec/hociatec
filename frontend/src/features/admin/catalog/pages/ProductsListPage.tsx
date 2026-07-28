import { Link } from 'react-router';
import type { CatalogProduct } from '@/features/catalog/api';

import { useAdminProductsList } from '../hooks/useAdminProductsList';
import { PageContainer } from '@/shared/components/PageContainer';
import { useDocumentTitle } from '@/shared/hooks/useDocumentTitle';
import { AdminListState, AdminTableShell } from '@/shared/components/admin/AdminDataView';
import { FilterBar } from '@/shared/components/filters/FilterBar';
import { ResetFiltersButton } from '@/shared/components/filters/ResetFiltersButton';
import { SearchFilter } from '@/shared/components/filters/SearchFilter';
import { SelectFilter } from '@/shared/components/filters/SelectFilter';
import { FeedbackMessage, PrimaryLink } from '@/shared/components/ui/page-state';

import '@/features/catalog/pages/CatalogPages.css';

const getStockLevel = (product: CatalogProduct) => product.totalStock ?? product.stock;

export const ProductsListPage = () => {
  useDocumentTitle('Admin - Produits');
  const {
    categories,
    loading,
    error,
    message,
    search,
    setSearch,
    filterCategory,
    setFilterCategory,
    stockFilter,
    setStockFilter,
    filteredProducts,
    resetFilters,
    handleDelete,
  } = useAdminProductsList();

  const hasActiveFilters =
    search.trim() !== '' || filterCategory !== 'all' || stockFilter !== 'all';

  return (
    <PageContainer
      size="admin"
      title="Produits"
      headerActions={<PrimaryLink to="/admin/catalog/products/new">Nouveau produit</PrimaryLink>}
    >
      <div className="mb-6 space-y-1">
        <p className="text-sm text-stone-600">
          {filteredProducts.length} produit{filteredProducts.length > 1 ? 's' : ''} affiché
          {filteredProducts.length > 1 ? 's' : ''}
        </p>
        <p className="text-sm text-stone-500">
          Recherchez un produit, contrôlez les stocks et ouvrez rapidement les fiches à corriger.
        </p>
      </div>

      {stockFilter === 'low' && (
        <div className="mb-6 rounded-2xl border border-amber-200 bg-amber-50 px-5 py-4 text-sm text-amber-900">
          <div className="font-semibold">Stock faible à traiter</div>
          <div className="mt-1">
            Cette vue affiche les produits dont le stock total est inférieur ou égal à 3. Ouvrez la
            fiche produit pour ajuster le stock ou préparer le réassort.
          </div>
        </div>
      )}

      <FilterBar
        rightActions={hasActiveFilters ? <ResetFiltersButton onReset={resetFilters} /> : null}
      >
        <SearchFilter
          value={search}
          onChange={setSearch}
          placeholder="Nom, SKU, slug ou marque..."
        />
        <SelectFilter
          value={filterCategory}
          onChange={setFilterCategory}
          options={[
            { value: 'all', label: 'Toutes les catégories' },
            ...categories.map((c) => ({ value: c.slug, label: c.name })),
          ]}
          ariaLabel="Catégorie"
        />
        <SelectFilter
          value={stockFilter}
          onChange={(value) => setStockFilter(value as 'all' | 'low')}
          options={[
            { value: 'all', label: 'Tous les stocks' },
            { value: 'low', label: 'Stock faible (≤ 3)' },
          ]}
          ariaLabel="Stock"
        />
      </FilterBar>

      {error && <FeedbackMessage>{error}</FeedbackMessage>}
      {message && <FeedbackMessage variant="success">{message}</FeedbackMessage>}

      <AdminListState
        loading={loading}
        isEmpty={filteredProducts.length === 0}
        loadingLabel="Chargement du catalogue admin..."
        emptyLabel="Aucun produit ne correspond à ces filtres. Modifiez la recherche ou réinitialisez les filtres pour revoir tout le catalogue."
      >
        <AdminTableShell>
          <table className="catalog-admin-table">
            <thead>
              <tr>
                <th scope="col">Produit</th>
                <th scope="col">Variantes</th>
                <th scope="col">Stock</th>
                <th scope="col">Actions</th>
              </tr>
            </thead>
            <tbody>
              {filteredProducts.map((product) => (
                <tr key={product.id}>
                  <th scope="row">
                    <strong className="catalog-admin-product-cell__title">{product.name}</strong>
                    <div className="muted">SKU {product.sku}</div>
                  </th>
                  <td>{product.variantsCount ?? 1}</td>
                  <td>
                    <div className="font-medium text-brand-900">{getStockLevel(product)}</div>
                    {getStockLevel(product) <= 0 ? (
                      <div className="mt-1 inline-flex rounded-full bg-red-100 px-2 py-1 text-xs font-semibold text-red-700">
                        Rupture
                      </div>
                    ) : getStockLevel(product) <= 3 ? (
                      <div className="mt-1 inline-flex rounded-full bg-amber-100 px-2 py-1 text-xs font-semibold text-amber-700">
                        Stock critique
                      </div>
                    ) : null}
                  </td>
                  <td>
                    <div className="catalog-admin-actions">
                      {getStockLevel(product) <= 3 ? (
                        <Link
                          to={`/admin/catalog/products/${product.id}/edit`}
                          className="catalog-admin-actions__edit"
                          aria-label={`Réapprovisionner le produit ${product.name}`}
                        >
                          Ajuster le stock
                        </Link>
                      ) : null}
                      <Link
                        to={`/catalogue/produits/${product.slug}`}
                        className="catalog-admin-actions__edit"
                        target="_blank"
                        rel="noreferrer"
                        aria-label={`Voir le produit ${product.name}`}
                      >
                        Voir
                      </Link>
                      <Link
                        to={`/admin/catalog/products/${product.id}/edit`}
                        className="catalog-admin-actions__edit"
                        aria-label={`Modifier le produit ${product.name}`}
                      >
                        Modifier
                      </Link>
                      <button
                        type="button"
                        className="catalog-admin-actions__delete"
                        onClick={() => void handleDelete(product.id)}
                        aria-label={`Supprimer le produit ${product.name}`}
                      >
                        Supprimer
                      </button>
                    </div>
                  </td>
                </tr>
              ))}
            </tbody>
          </table>
        </AdminTableShell>
      </AdminListState>
    </PageContainer>
  );
};
