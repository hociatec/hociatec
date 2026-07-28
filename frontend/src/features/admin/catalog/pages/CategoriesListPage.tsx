import { getHttpErrorMessage } from '@/shared/lib/httpClient';
import { useEffect, useMemo, useState } from 'react';
import { Link } from 'react-router';

import { deleteCategory, fetchAdminCategories, type CatalogCategory } from '@/features/catalog/api';
import { PageContainer } from '@/shared/components/PageContainer';
import { AdminListState, AdminTableShell } from '@/shared/components/admin/AdminDataView';
import { useDocumentTitle } from '@/shared/hooks/useDocumentTitle';
import { SearchFilter } from '@/shared/components/filters/SearchFilter';
import { useConfirm } from '@/shared/components/ui/confirm';
import { FeedbackMessage, PrimaryLink } from '@/shared/components/ui/page-state';

export const CategoriesListPage = () => {
  useDocumentTitle('Admin - Catégories');

  const [categories, setCategories] = useState<CatalogCategory[]>([]);
  const [loading, setLoading] = useState(true);
  const [error, setError] = useState<string | null>(null);
  const [message, setMessage] = useState<string | null>(null);
  const [search, setSearch] = useState('');
  const confirm = useConfirm();

  const loadCategories = async () => {
    setLoading(true);
    setError(null);

    try {
      const items = await fetchAdminCategories();
      setCategories(items);
    } catch (err) {
      setError(getHttpErrorMessage(err, 'Impossible de charger les catégories.'));
    } finally {
      setLoading(false);
    }
  };

  useEffect(() => {
    void loadCategories();
  }, []);

  const handleDelete = async (categoryId: number) => {
    const category = categories.find((item) => item.id === categoryId);
    const categoryLabel = category ? `"${category.name}"` : 'cette categorie';

    const confirmed = await confirm({
      title: 'Supprimer la catégorie',
      description: `Supprimer ${categoryLabel} ?`,
      confirmLabel: 'Supprimer',
      cancelLabel: 'Annuler',
    });

    if (!confirmed) {
      return;
    }

    setError(null);
    setMessage(null);

    try {
      const response = await deleteCategory(categoryId);
      await loadCategories();
      setMessage(response.message ?? 'La catégorie a bien été supprimée.');
    } catch (err) {
      setError(getHttpErrorMessage(err, 'Impossible de supprimer la catégorie.'));
    }
  };

  const filteredCategories = useMemo(() => {
    const term = search.trim().toLowerCase();
    if (!term) return categories;

    return categories.filter(
      (category) =>
        category.name.toLowerCase().includes(term) || category.slug.toLowerCase().includes(term),
    );
  }, [categories, search]);

  return (
    <PageContainer
      size="admin"
      title="Catégories"
      headerActions={
        <PrimaryLink to="/admin/catalog/categories/new">Ajouter une catégorie</PrimaryLink>
      }
    >
      <div className="mb-6 space-y-1">
        <p className="text-sm text-stone-600">
          {filteredCategories.length} catégorie{filteredCategories.length > 1 ? 's' : ''} affichée
          {filteredCategories.length > 1 ? 's' : ''}
        </p>
        <p className="text-sm text-stone-500">Filtrez par nom ou slug.</p>
      </div>

      <div className="mb-6 max-w-sm">
        <SearchFilter
          value={search}
          onChange={setSearch}
          placeholder="Rechercher par nom ou slug..."
        />
      </div>

      {error && <FeedbackMessage>{error}</FeedbackMessage>}
      {message && <FeedbackMessage variant="success">{message}</FeedbackMessage>}

      <AdminListState
        loading={loading}
        isEmpty={filteredCategories.length === 0}
        loadingLabel="Chargement des catégories..."
        emptyLabel="Aucune catégorie ne correspond à votre recherche."
      >
        <AdminTableShell>
          <table className="catalog-admin-table">
            <thead>
              <tr>
                <th scope="col">Nom</th>
                <th scope="col">Slug</th>
                <th scope="col">Visibilité</th>
                <th scope="col">Actions</th>
              </tr>
            </thead>
            <tbody>
              {filteredCategories.map((category) => (
                <tr key={category.id}>
                  <th scope="row">
                    <strong>{category.name}</strong>
                    {category.description && <p className="muted mt-1">{category.description}</p>}
                  </th>
                  <td>{category.slug}</td>
                  <td>{category.isVisible ? 'Visible' : 'Masquée'}</td>
                  <td>
                    <div className="catalog-admin-actions">
                      <Link
                        to={`/admin/catalog/categories/${category.id}/edit`}
                        className="catalog-admin-actions__edit"
                        aria-label={`Modifier la categorie ${category.name}`}
                      >
                        Modifier
                      </Link>
                      <button
                        type="button"
                        className="catalog-admin-actions__delete"
                        onClick={() => void handleDelete(category.id)}
                        aria-label={`Supprimer la categorie ${category.name}`}
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
