import { Link } from 'react-router';

import type { CatalogProduct } from '@/features/catalog/adminApi';
import { AdminListState, AdminTableShell } from '@/shared/components/admin/AdminDataView';

const getStockLevel = (product: CatalogProduct) => product.totalStock ?? product.stock;

export const AdminProductsTable = ({
  loading,
  products,
  onDelete,
}: {
  loading: boolean;
  products: CatalogProduct[];
  onDelete: (id: number) => void;
}) => (
  <AdminListState
    loading={loading}
    isEmpty={products.length === 0}
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
          {products.map((product) => (
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
                    rel="noopener noreferrer"
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
                    onClick={() => onDelete(product.id)}
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
);
