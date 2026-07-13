import { useEffect, useMemo, useState } from 'react';
import { Link } from 'react-router-dom';

import { deleteCategory, fetchAdminCategories } from '@/features/catalog/api';
import { useRequireAdmin } from '@/features/admin/hooks/useRequireAdmin';
import type { CatalogCategory } from '@/features/catalog/api';
import { PageContainer } from '@/shared/components/PageContainer';
import { useDocumentTitle } from '@/shared/hooks/useDocumentTitle';

export const CategoriesListPage = () => {
  useDocumentTitle('Admin - Catégories');

  const { isAdmin, loading: guardLoading } = useRequireAdmin();
  const [categories, setCategories] = useState<CatalogCategory[]>([]);
  const [loading, setLoading] = useState(true);
  const [error, setError] = useState<string | null>(null);
  const [message, setMessage] = useState<string | null>(null);
  const [search, setSearch] = useState('');

  useEffect(() => {
    if (!isAdmin) {
      return;
    }

    void loadCategories();
  }, [isAdmin]);

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

  const handleDelete = async (categoryId: number) => {
    if (!window.confirm('Supprimer cette catégorie ?')) {
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
    if (!search.trim()) {
      return categories;
    }

    const lowerSearch = search.trim().toLowerCase();

    return categories.filter(
      (category) =>
        category.name.toLowerCase().includes(lowerSearch) ||
        category.slug.toLowerCase().includes(lowerSearch),
    );
  }, [categories, search]);

  if (guardLoading) {
    return (
      <PageContainer title="Catégories">
        <p className="muted">Vérification des droits...</p>
      </PageContainer>
    );
  }

  if (!isAdmin) {
    return (
      <PageContainer title="Catégories">
        <div className="register-form__alert">Accès restreint aux administrateurs.</div>
      </PageContainer>
    );
  }

  return (
    <PageContainer
      title="Catégories"
      headerActions={
        <Link to="/admin/catalog/categories/new" className="register-form__submit">
          Ajouter une catégorie
        </Link>
      }
    >
      <div style={{ display: 'flex', gap: 12, marginBottom: 16, flexWrap: 'wrap' }}>
        <input
          type="search"
          value={search}
          onChange={(event) => setSearch(event.target.value)}
          placeholder="Rechercher par nom ou slug..."
          className="register-form__input"
          style={{ maxWidth: 320 }}
        />
      </div>

      {error && <div className="register-form__alert">{error}</div>}
      {message && (
        <div className="register-form__alert" style={{ background: '#ecfdf5', color: '#047857' }}>
          {message}
        </div>
      )}

      {loading ? (
        <p className="muted">Chargement des catégories...</p>
      ) : filteredCategories.length === 0 ? (
        <p className="muted">Aucune catégorie ne correspond à votre recherche.</p>
      ) : (
        <table style={{ width: '100%', borderCollapse: 'collapse', marginTop: 12 }}>
          <thead>
            <tr>
              <th style={{ textAlign: 'left', padding: 8 }}>Nom</th>
              <th style={{ textAlign: 'left', padding: 8 }}>Slug</th>
              <th style={{ textAlign: 'center', padding: 8, width: 120 }}>Visibilité</th>
              <th style={{ textAlign: 'center', padding: 8, width: 160 }}>Actions</th>
            </tr>
          </thead>
          <tbody>
            {filteredCategories.map((category) => (
              <tr key={category.id} style={{ borderTop: '1px solid rgba(148,163,184,.25)' }}>
                <td style={{ padding: 8 }}>
                  <strong>{category.name}</strong>
                  {category.description && (
                    <p className="muted" style={{ marginTop: 4 }}>
                      {category.description}
                    </p>
                  )}
                </td>
                <td style={{ padding: 8 }}>{category.slug}</td>
                <td style={{ textAlign: 'center', padding: 8 }}>
                  {category.isVisible ? 'Visible' : 'Masquée'}
                </td>
                <td style={{ padding: 8, display: 'flex', gap: 8, justifyContent: 'center' }}>
                  <Link
                    to={`/admin/catalog/categories/${category.id}/edit`}
                    className="register-form__submit"
                    style={{ background: '#e5e7eb', color: '#111827' }}
                  >
                    Modifier
                  </Link>
                  <button
                    type="button"
                    className="register-form__submit"
                    style={{ background: '#fee2e2', color: '#991b1b' }}
                    onClick={() => void handleDelete(category.id)}
                  >
                    Supprimer
                  </button>
                </td>
              </tr>
            ))}
          </tbody>
        </table>
      )}
    </PageContainer>
  );
};
