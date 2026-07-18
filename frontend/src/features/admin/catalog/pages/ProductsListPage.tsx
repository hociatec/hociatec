import { useEffect, useMemo, useState } from 'react';
import { Link, useSearchParams } from 'react-router-dom';

import {
  deleteProduct,
  fetchAdminCategories,
  fetchAdminProducts,
  type CatalogCategory,
  type CatalogProduct,
} from '@/features/catalog/api';
import { PageContainer } from '@/shared/components/PageContainer';
import { useDocumentTitle } from '@/shared/hooks/useDocumentTitle';
import { FilterBar } from '@/shared/components/filters/FilterBar';
import { SearchFilter } from '@/shared/components/filters/SearchFilter';
import { SelectFilter } from '@/shared/components/filters/SelectFilter';
import { groupCatalogProducts } from '@/features/catalog/utils/groupProducts';

import '@/features/catalog/pages/CatalogPages.css';

const getStockLevel = (product: CatalogProduct) => product.totalStock ?? product.stock;

export const ProductsListPage = () => {
  useDocumentTitle('Admin - Produits');
  const [searchParams, setSearchParams] = useSearchParams();

  const [products, setProducts] = useState<CatalogProduct[]>([]);
  const [categories, setCategories] = useState<CatalogCategory[]>([]);
  const [loading, setLoading] = useState(true);
  const [error, setError] = useState<string | null>(null);
  const [message, setMessage] = useState<string | null>(null);
  const [search, setSearch] = useState(searchParams.get('search') ?? '');
  const [filterCategory, setFilterCategory] = useState<string>('all');
  const [stockFilter, setStockFilter] = useState<'all' | 'low'>(
    (searchParams.get('stock') as 'all' | 'low' | null) ?? 'all',
  );

  useEffect(() => {
    setLoading(true);
    setError(null);

    void Promise.all([fetchAdminProducts(), fetchAdminCategories()])
      .then(([productList, categoryList]) => {
        setProducts(productList);
        setCategories(categoryList);
      })
      .catch((err: any) => setError(err?.message ?? 'Impossible de charger les produits.'))
      .finally(() => setLoading(false));
  }, []);

  const handleDelete = async (productId: number) => {
    const product = products.find((item) => item.id === productId);
    const productLabel = product ? `"${product.name}"` : 'ce produit';

    if (!window.confirm(`Supprimer ${productLabel} ?`)) {
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
    const groupedProducts = groupCatalogProducts(products);

    return groupedProducts.filter((product) => {
      const matchSearch =
        term.length === 0 ||
        product.name.toLowerCase().includes(term) ||
        product.slug.toLowerCase().includes(term) ||
        product.sku.toLowerCase().includes(term) ||
        (product.brand?.toLowerCase().includes(term) ?? false);

      const matchCategory = !filterSlug || product.category.slug === filterSlug;
      const totalStock = getStockLevel(product);
      const matchStock = stockFilter !== 'low' || totalStock <= 3;

      return matchSearch && matchCategory && matchStock;
    });
  }, [products, search, filterCategory, stockFilter]);

  useEffect(() => {
    const next = new URLSearchParams();
    if (search.trim() !== '') next.set('search', search.trim());
    if (filterCategory !== 'all') next.set('category', filterCategory);
    if (stockFilter !== 'all') next.set('stock', stockFilter);
    setSearchParams(next, { replace: true });
  }, [search, filterCategory, stockFilter, setSearchParams]);

  useEffect(() => {
    const categoryParam = searchParams.get('category');
    if (categoryParam) {
      setFilterCategory(categoryParam);
    }
  }, [searchParams]);

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

      {stockFilter === 'low' && (
        <div className="mb-6 rounded-2xl border border-amber-200 bg-amber-50 px-5 py-4 text-sm text-amber-900">
          <div className="font-semibold">Produits à réapprovisionner</div>
          <div className="mt-1">
            Cette vue affiche uniquement les produits avec un stock inférieur ou égal à 3.
            Utilise le bouton <span className="font-semibold">Réapprovisionner</span> pour ouvrir directement la fiche produit.
          </div>
        </div>
      )}

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
        <div className="overflow-x-auto rounded-3xl border border-slate-200 bg-white shadow-sm">
          <table className="catalog-admin-table">
            <thead>
              <tr>
                <th scope="col">Produit</th>
                <th scope="col">Nombre de variantes</th>
                <th scope="col">Stock total</th>
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
                    <div className="font-medium text-slate-900">{getStockLevel(product)}</div>
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
                          Réapprovisionner
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
        </div>
      )}
    </PageContainer>
  );
};
