import { useEffect, useMemo, useState } from 'react';
import { Link } from 'react-router-dom';

import { deleteBrand, fetchAdminBrands, type CatalogBrand } from '@/features/catalog/api';
import { SearchFilter } from '@/shared/components/filters/SearchFilter';
import { PageContainer } from '@/shared/components/PageContainer';
import { useDocumentTitle } from '@/shared/hooks/useDocumentTitle';

export const BrandsListPage = () => {
  useDocumentTitle('Admin - Marques');

  const [brands, setBrands] = useState<CatalogBrand[]>([]);
  const [loading, setLoading] = useState(true);
  const [error, setError] = useState<string | null>(null);
  const [message, setMessage] = useState<string | null>(null);
  const [search, setSearch] = useState('');

  const loadBrands = async () => {
    setLoading(true);
    setError(null);

    try {
      const items = await fetchAdminBrands();
      setBrands(items);
    } catch (err: any) {
      setError(err?.message ?? 'Impossible de charger les marques.');
    } finally {
      setLoading(false);
    }
  };

  useEffect(() => {
    void loadBrands();
  }, []);

  const handleDelete = async (brand: CatalogBrand) => {
    if (!window.confirm(`Supprimer la marque "${brand.name}" ? Les produits liés perdront cette marque.`)) {
      return;
    }

    setError(null);
    setMessage(null);

    try {
      await deleteBrand(brand.id);
      await loadBrands();
      setMessage('Marque supprimée.');
    } catch (err: any) {
      setError(err?.message ?? 'Impossible de supprimer la marque.');
    }
  };

  const filteredBrands = useMemo(() => {
    const term = search.trim().toLowerCase();

    if (!term) {
      return brands;
    }

    return brands.filter((brand) => brand.name.toLowerCase().includes(term));
  }, [brands, search]);

  return (
    <PageContainer
      title="Marques"
      headerActions={
        <Link
          to="/admin/catalog/brands/new"
          className="inline-flex items-center rounded-full bg-slate-900 px-4 py-2 text-sm font-semibold text-white transition hover:bg-slate-800"
        >
          Ajouter une marque
        </Link>
      }
    >
      <div className="mb-6 space-y-1">
        <p className="text-sm text-slate-600">
          {filteredBrands.length} marque{filteredBrands.length > 1 ? 's' : ''} affichée
          {filteredBrands.length > 1 ? 's' : ''}
        </p>
        <p className="text-sm text-slate-500">Recherchez une marque existante et gérez son libellé.</p>
      </div>

      <div className="mb-6 max-w-sm">
        <SearchFilter
          value={search}
          onChange={setSearch}
          placeholder="Rechercher une marque..."
        />
      </div>

      {error && <div className="register-form__alert">{error}</div>}
      {message && (
        <div className="register-form__alert" style={{ background: '#ecfdf5', color: '#047857' }}>
          {message}
        </div>
      )}

      {loading ? (
        <div className="rounded-2xl border border-dashed border-slate-200 bg-slate-50 px-4 py-8 text-center text-slate-600">
          Chargement des marques...
        </div>
      ) : filteredBrands.length === 0 ? (
        <div className="rounded-2xl border border-dashed border-slate-200 bg-slate-50 px-4 py-8 text-center text-slate-600">
          Aucune marque ne correspond à votre recherche.
        </div>
      ) : (
        <div className="overflow-x-auto rounded-3xl border border-slate-200 bg-white shadow-sm">
          <table className="catalog-admin-table">
            <thead>
              <tr>
                <th scope="col">Nom</th>
                <th scope="col">Produits liés</th>
                <th scope="col">Actions</th>
              </tr>
            </thead>
            <tbody>
              {filteredBrands.map((brand) => (
                <tr key={brand.id}>
                  <th scope="row">
                    <strong>{brand.name}</strong>
                  </th>
                  <td>{brand.productsCount ?? 0}</td>
                  <td>
                    <div className="catalog-admin-actions">
                      <Link
                        to={`/admin/catalog/brands/${brand.id}/edit`}
                        className="catalog-admin-actions__edit"
                        aria-label={`Modifier la marque ${brand.name}`}
                      >
                        Modifier
                      </Link>
                      <button
                        type="button"
                        className="catalog-admin-actions__delete"
                        onClick={() => void handleDelete(brand)}
                        aria-label={`Supprimer la marque ${brand.name}`}
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
