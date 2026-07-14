import { useEffect, useMemo, useState } from 'react';
import { Link } from 'react-router-dom';

import {
  deleteProduct,
  fetchAdminCategories,
  fetchAdminProducts,
  type CatalogCategory,
  type CatalogProduct,
} from '@/features/catalog/api';
import { useRequireAdmin } from '@/features/admin/hooks/useRequireAdmin';
import { PageContainer } from '@/shared/components/PageContainer';
import { useDocumentTitle } from '@/shared/hooks/useDocumentTitle';
import { FilterBar } from '@/shared/components/filters/FilterBar';
import { SearchFilter } from '@/shared/components/filters/SearchFilter';
import { SelectFilter } from '@/shared/components/filters/SelectFilter';
import { groupMatchesFilters } from '@/features/catalog/utils/groupProducts';

import '@/features/catalog/pages/CatalogPages.css';

export const ProductsListPage = () => {
  useDocumentTitle('Admin - Produits');

  const { isAdmin, loading: guardLoading } = useRequireAdmin();
  const [products, setProducts] = useState<CatalogProduct[]>([]);
  const [categories, setCategories] = useState<CatalogCategory[]>([]);
  const [loading, setLoading] = useState(true);
  const [error, setError] = useState<string | null>(null);
  const [message, setMessage] = useState<string | null>(null);
  const [search, setSearch] = useState('');
  const [filterCategory, setFilterCategory] = useState<string>('all');

  useEffect(() => {
    if (!isAdmin) {
      return;
    }

    setLoading(true);
    setError(null);

    void Promise.all([fetchAdminProducts(), fetchAdminCategories()])
      .then(([productList, categoryList]) => {
        setProducts(productList);
        setCategories(categoryList);
      })
      .catch((err: any) => setError(err?.message ?? 'Impossible de charger les produits.'))
      .finally(() => setLoading(false));
  }, [isAdmin]);

  const handleDelete = async (productId: number) => {
    if (!window.confirm('Supprimer ce produit ?')) {
      return;
    }

    setError(null);
    setMessage(null);

    try {
      await deleteProduct(productId);
      setProducts((prev) => prev.filter((product) => product.id !== productId));
      setMessage('Produit supprimé.');
    } catch (err: any) {
      setError(err?.message ?? 'Impossible de supprimer le produit.');
    }
  };

  const filteredProducts = useMemo(() => {
    const term = search.trim().toLowerCase();
    const filterSlug = filterCategory === 'all' ? null : filterCategory;

    return groupMatchesFilters(products, (product) => {
      const matchSearch =
        term.length === 0 ||
        product.name.toLowerCase().includes(term) ||
        product.slug.toLowerCase().includes(term) ||
        product.sku.toLowerCase().includes(term) ||
        (product.brand?.toLowerCase().includes(term) ?? false);

      const matchCategory = !filterSlug || product.category.slug === filterSlug;

      return matchSearch && matchCategory;
    });
  }, [products, search, filterCategory]);

  if (guardLoading) {
    return (
      <PageContainer title="Produits">
        <p className="muted">Vérification des droits...</p>
      </PageContainer>
    );
  }

  if (!isAdmin) {
    return (
      <PageContainer title="Produits">
        <div className="register-form__alert">Accès restreint aux administrateurs.</div>
      </PageContainer>
    );
  }

  return (
    <PageContainer
      title="Produits"
      headerActions={
        <Link
          to="/admin/catalog/products/new"
          className="inline-flex items-center rounded-full bg-slate-900 px-4 py-2 text-sm font-semibold text-white transition hover:bg-slate-800"
        >
          Ajouter un produit
        </Link>
      }
    >
      <div className="mb-6 space-y-1">
        <p className="text-sm text-slate-600">
          {filteredProducts.length} produit{filteredProducts.length > 1 ? 's' : ''} affiché
          {filteredProducts.length > 1 ? 's' : ''}
        </p>
        <p className="text-sm text-slate-500">
          Filtrez par nom, slug, SKU, marque ou catégorie.
        </p>
      </div>

      <FilterBar>
        <SearchFilter
          value={search}
          onChange={setSearch}
          placeholder="Rechercher par nom, slug ou SKU..."
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
      </FilterBar>

      {error && <div className="register-form__alert">{error}</div>}
      {message && (
        <div className="register-form__alert" style={{ background: '#ecfdf5', color: '#047857' }}>
          {message}
        </div>
      )}

      {loading ? (
        <div className="rounded-2xl border border-dashed border-slate-200 bg-slate-50 px-4 py-8 text-center text-slate-600">
          Chargement des produits...
        </div>
      ) : filteredProducts.length === 0 ? (
        <div className="rounded-2xl border border-dashed border-slate-200 bg-slate-50 px-4 py-8 text-center text-slate-600">
          Aucun produit ne correspond à vos filtres.
        </div>
      ) : (
        <div className="overflow-hidden rounded-3xl border border-slate-200 bg-white shadow-sm">
          <table className="catalog-admin-table">
            <thead>
              <tr>
                <th>Produit</th>
                <th>Nombre de variantes</th>
                <th>Stock total</th>
                <th>Actions</th>
              </tr>
            </thead>
            <tbody>
              {filteredProducts.map((product) => (
                <tr key={product.id}>
                  <td>
                    <strong className="catalog-admin-product-cell__title">{product.name}</strong>
                  </td>
                  <td>{product.variantsCount ?? 1}</td>
                  <td>{product.totalStock ?? product.stock}</td>
                  <td>
                    <div className="catalog-admin-actions">
                      <Link
                        to={`/catalogue/produits/${product.slug}`}
                        className="catalog-admin-actions__edit"
                        target="_blank"
                        rel="noreferrer"
                      >
                        Voir
                      </Link>
                      <Link
                        to={`/admin/catalog/products/${product.id}/edit`}
                        className="catalog-admin-actions__edit"
                      >
                        Modifier
                      </Link>
                      <button
                        type="button"
                        className="catalog-admin-actions__delete"
                        onClick={() => void handleDelete(product.id)}
                      >
                        Supprimer
                      </button>
                    </div>
                  </td>
                </tr>
              ))}
            </tbody>
          </table>
        </div>
      )}
    </PageContainer>
  );
};
