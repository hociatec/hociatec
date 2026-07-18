import { useEffect, useMemo, useState } from 'react';
import { Link } from 'react-router-dom';

import { deleteCategory, fetchAdminCategories, type CatalogCategory } from '@/features/catalog/api';
import { PageContainer } from '@/shared/components/PageContainer';
import { useDocumentTitle } from '@/shared/hooks/useDocumentTitle';
import { SearchFilter } from '@/shared/components/filters/SearchFilter';

export const CategoriesListPage = () => {
  useDocumentTitle('Admin - Catégories');

  const [categories, setCategories] = useState<CatalogCategory[]>([]);
  const [loading, setLoading] = useState(true);
  const [error, setError] = useState<string | null>(null);
  const [message, setMessage] = useState<string | null>(null);
  const [search, setSearch] = useState('');

  const loadCategories = async () => {
    setLoading(true);
    setError(null);

    try {
      const items = await fetchAdminCategories();
      setCategories(items);
    } catch (err: any) {
      setError(err?.message ?? 'Impossible de charger les catégories.');
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

    if (!window.confirm(`Supprimer ${categoryLabel} ?`)) {
      return;
    }

    setError(null);
    setMessage(null);

    try {
      await deleteCategory(categoryId);
      await loadCategories();
      setMessage('Catégorie supprimée.');
    } catch (err: any) {
      setError(err?.message ?? 'Impossible de supprimer la catégorie.');
    }
  };

  const filteredCategories = useMemo(() => {
    const term = search.trim().toLowerCase();
    if (!term) return categories;

    return categories.filter(
      (category) =>
        category.name.toLowerCase().includes(term) ||
        category.slug.toLowerCase().includes(term),
    );
  }, [categories, search]);

  return (
    <PageContainer
      title="Catégories"
      headerActions={
        <Link
          to="/admin/catalog/categories/new"
          className="inline-flex items-center rounded-full bg-slate-900 px-4 py-2 text-sm font-semibold text-white transition hover:bg-slate-800"
        >
          Ajouter une catégorie
        </Link>
      }
    >
      <div className="mb-6 space-y-1">
        <p className="text-sm text-slate-600">
          {filteredCategories.length} catégorie{filteredCategories.length > 1 ? 's' : ''} affichée
          {filteredCategories.length > 1 ? 's' : ''}
        </p>
        <p className="text-sm text-slate-500">
          Filtrez par nom ou slug.
        </p>
      </div>

      <div className="mb-6 max-w-sm">
        <SearchFilter
          value={search}
          onChange={setSearch}
          placeholder="Rechercher par nom ou slug..."
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
          Chargement des catégories...
        </div>
      ) : filteredCategories.length === 0 ? (
        <div className="rounded-2xl border border-dashed border-slate-200 bg-slate-50 px-4 py-8 text-center text-slate-600">
          Aucune catégorie ne correspond à votre recherche.
        </div>
      ) : (
        <div className="overflow-hidden rounded-3xl border border-slate-200 bg-white shadow-sm">
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
                    {category.description && (
                      <p className="muted" style={{ marginTop: 4 }}>
                        {category.description}
                      </p>
                    )}
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
        </div>
      )}
    </PageContainer>
  );
};
