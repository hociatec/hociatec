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

import '@/features/catalog/pages/CatalogPages.css';

const formatPrice = (priceCents: number) =>
  new Intl.NumberFormat('fr-FR', {
    style: 'currency',
    currency: 'EUR',
  }).format(priceCents / 100);

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

    return products.filter((product) => {
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
        <Link to="/admin/catalog/products/new" className="register-form__submit">
          Ajouter un produit
        </Link>
      }
    >
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
        <p className="muted">Chargement des produits...</p>
      ) : filteredProducts.length === 0 ? (
        <p className="muted">Aucun produit ne correspond a vos filtres.</p>
      ) : (
        <table className="catalog-admin-table">
          <thead>
            <tr>
              <th>Produit</th>
              <th>Groupe</th>
              <th>Catégorie</th>
              <th>Prix</th>
              <th>Stock</th>
              <th>Statut</th>
              <th>Actions</th>
            </tr>
          </thead>
          <tbody>
            {filteredProducts.map((product) => (
              <tr key={product.id}>
                <td>
                  <div className="catalog-admin-product-cell">
                    <strong>{product.name}</strong>
                    {product.brand && (
                      <span className="muted">Marque : {product.brand}</span>
                    )}
                    {product.variantGroup && (
                      <span className="muted">Groupe : {product.variantGroup}</span>
                    )}
                    {product.releaseYear && (
                      <span className="muted">Modèle : {product.releaseYear}</span>
                    )}
                    {product.color && (
                      <span className="muted">Couleur : {product.color}</span>
                    )}
                    {product.storageCapacity && (
                      <span className="muted">Stockage : {product.storageCapacity}</span>
                    )}
                    {product.memoryRam && (
                      <span className="muted">RAM : {product.memoryRam}</span>
                    )}
                    <span className="muted">Slug : {product.slug}</span>
                    <span className="muted">SKU {product.sku}</span>
                    {product.isFeaturedHome && (
                      <span className="catalog-featured-badge">Accueil</span>
                    )}
                  </div>
                </td>
                <td>{product.variantGroup ?? '-'}</td>
                <td>{product.category.name}</td>
                <td>
                  {formatPrice(product.priceCents)}{product.sellingType === 'rental' ? ' / mois' : ''}
                </td>
                <td>
                  <div className="catalog-admin-product-cell">
                    <strong>{product.stock}</strong>
                    <span className="muted">
                      {product.color ? `Couleur : ${product.color}` : 'Variante par défaut'}
                    </span>
                  </div>
                </td>
                <td>{product.isPublished ? 'Publié' : 'Brouillon'}</td>
                <td>
                  <div className="catalog-admin-actions">
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
      )}
    </PageContainer>
  );
};
