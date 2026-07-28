import { getHttpErrorMessage } from '@/shared/lib/httpClient';
import { useEffect, useMemo, useState } from 'react';
import { Link } from 'react-router';

import { deleteBrand, fetchAdminBrands, type CatalogBrand } from '@/features/catalog/api';
import { SearchFilter } from '@/shared/components/filters/SearchFilter';
import { PageContainer } from '@/shared/components/layout/PageContainer';
import { AdminListState, AdminTableShell } from '@/shared/components/admin/AdminDataView';
import { useConfirm } from '@/shared/components/ui/confirm';
import { FeedbackMessage, PrimaryLink } from '@/shared/components/ui/page-state';
import { useDocumentTitle } from '@/shared/hooks/useDocumentTitle';

export const BrandsListPage = () => {
  useDocumentTitle('Admin - Marques');

  const [brands, setBrands] = useState<CatalogBrand[]>([]);
  const [loading, setLoading] = useState(true);
  const [error, setError] = useState<string | null>(null);
  const [message, setMessage] = useState<string | null>(null);
  const [search, setSearch] = useState('');
  const confirm = useConfirm();

  const loadBrands = async () => {
    setLoading(true);
    setError(null);

    try {
      const items = await fetchAdminBrands();
      setBrands(items);
    } catch (err) {
      setError(getHttpErrorMessage(err, 'Impossible de charger les marques.'));
    } finally {
      setLoading(false);
    }
  };

  useEffect(() => {
    void loadBrands();
  }, []);

  const handleDelete = async (brand: CatalogBrand) => {
    const confirmed = await confirm({
      title: 'Supprimer la marque',
      description: `Supprimer la marque "${brand.name}" ? Les produits liés perdront cette marque.`,
      confirmLabel: 'Supprimer',
      cancelLabel: 'Annuler',
    });

    if (!confirmed) {
      return;
    }

    setError(null);
    setMessage(null);

    try {
      const response = await deleteBrand(brand.id);
      await loadBrands();
      setMessage(response.message ?? 'La marque a bien été supprimée.');
    } catch (err) {
      setError(getHttpErrorMessage(err, 'Impossible de supprimer la marque.'));
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
      size="admin"
      title="Marques"
      headerActions={<PrimaryLink to="/admin/catalog/brands/new">Ajouter une marque</PrimaryLink>}
    >
      <div className="mb-6 space-y-1">
        <p className="text-sm text-stone-600">
          {filteredBrands.length} marque{filteredBrands.length > 1 ? 's' : ''} affichée
          {filteredBrands.length > 1 ? 's' : ''}
        </p>
        <p className="text-sm text-stone-500">
          Recherchez une marque existante et gérez son libellé.
        </p>
      </div>

      <div className="mb-6 max-w-sm">
        <SearchFilter value={search} onChange={setSearch} placeholder="Rechercher une marque..." />
      </div>

      {error && <FeedbackMessage>{error}</FeedbackMessage>}
      {message && <FeedbackMessage variant="success">{message}</FeedbackMessage>}

      <AdminListState
        loading={loading}
        isEmpty={filteredBrands.length === 0}
        loadingLabel="Chargement des marques..."
        emptyLabel="Aucune marque ne correspond à votre recherche."
      >
        <AdminTableShell>
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
        </AdminTableShell>
      </AdminListState>
    </PageContainer>
  );
};
