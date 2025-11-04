import { useEffect, useMemo, useState } from 'react';
import { useNavigate, useParams } from 'react-router-dom';

import {
  createCategory,
  fetchAdminCategory,
  updateCategory,
  type CatalogCategory,
  type UpsertCategoryPayload,
} from '@/features/catalog/api';
import { useRequireAdmin } from '@/features/admin/hooks/useRequireAdmin';
import { PageContainer } from '@/shared/components/PageContainer';
import { useDocumentTitle } from '@/shared/hooks/useDocumentTitle';

type CategoryFormState = {
  name: string;
  slug: string;
  description: string;
  isVisible: boolean;
};

const emptyForm: CategoryFormState = {
  name: '',
  slug: '',
  description: '',
  isVisible: true,
};

const slugify = (value: string) =>
  value
    .normalize('NFD')
    .replace(/[\u0300-\u036f]/g, '')
    .toLowerCase()
    .replace(/[^a-z0-9]+/g, '-')
    .replace(/^-+|-+$/g, '');

export const CategoryFormPage = () => {
  const { categoryId } = useParams();
  const isEdit = useMemo(() => Boolean(categoryId), [categoryId]);
  const navigate = useNavigate();

  useDocumentTitle(isEdit ? 'Admin - Modifier une categorie' : 'Admin - Nouvelle categorie');

  const { isAdmin, loading: guardLoading } = useRequireAdmin();
  const [form, setForm] = useState<CategoryFormState>(emptyForm);
  const [loading, setLoading] = useState(false);
  const [initialLoading, setInitialLoading] = useState(false);
  const [error, setError] = useState<string | null>(null);
  const [message, setMessage] = useState<string | null>(null);

  useEffect(() => {
    if (!isAdmin || !isEdit) {
      return;
    }

    const loadCategory = async () => {
      setInitialLoading(true);
      setError(null);

      try {
        const category = await fetchAdminCategory(Number(categoryId));
        populateForm(category);
      } catch (err: any) {
        setError(err?.message ?? 'Impossible de charger la categorie.');
      } finally {
        setInitialLoading(false);
      }
    };

    void loadCategory();
  }, [isAdmin, isEdit, categoryId]);

  const populateForm = (category: CatalogCategory) => {
    setForm({
      name: category.name,
      slug: category.slug,
      description: category.description ?? '',
      isVisible: category.isVisible,
    });
  };

  const handleChange = (event: React.ChangeEvent<HTMLInputElement | HTMLTextAreaElement>) => {
    const { name, value, type } = event.target;

    if (type === 'checkbox') {
      const input = event.target as HTMLInputElement;
      setForm((prev) => ({ ...prev, [name]: input.checked }));
      return;
    }

    if (name === 'name') {
      const generatedSlug = slugify(value);
      setForm((prev) => {
        const shouldSyncSlug =
          prev.slug.trim() === '' || prev.slug === slugify(prev.name);
        return {
          ...prev,
          name: value,
          slug: shouldSyncSlug ? generatedSlug : prev.slug,
        };
      });
      return;
    }

    if (name === 'slug') {
      setForm((prev) => ({ ...prev, slug: slugify(value) }));
      return;
    }

    setForm((prev) => ({ ...prev, [name]: value }));
  };

  const parsePayload = (): UpsertCategoryPayload => ({
    name: form.name.trim(),
    slug: form.slug.trim() || null,
    description: form.description.trim() || null,
    isVisible: form.isVisible,
  });

  const handleSubmit = async (event: React.FormEvent) => {
    event.preventDefault();

    const payload = parsePayload();

    if (!payload.name) {
      setError('Le nom de la categorie est requis.');
      return;
    }

    setLoading(true);
    setError(null);
    setMessage(null);

    try {
      if (isEdit) {
        await updateCategory(Number(categoryId), payload);
        setMessage('Categorie mise a jour.');
      } else {
        await createCategory(payload);
        setMessage('Categorie creee.');
        setForm(emptyForm);
      }

      setTimeout(() => {
        navigate('/admin/catalog/categories');
      }, 600);
    } catch (err: any) {
      setError(err?.message ?? 'Impossible d\'enregistrer la categorie.');
    } finally {
      setLoading(false);
    }
  };

  if (guardLoading) {
    return (
      <PageContainer title={isEdit ? 'Modifier une categorie' : 'Nouvelle categorie'}>
        <p className="muted">Verification des droits...</p>
      </PageContainer>
    );
  }

  if (!isAdmin) {
    return (
      <PageContainer title={isEdit ? 'Modifier une categorie' : 'Nouvelle categorie'}>
        <div className="register-form__alert">Acces restreint aux administrateurs.</div>
      </PageContainer>
    );
  }

  return (
    <PageContainer
      title={isEdit ? 'Modifier une categorie' : 'Nouvelle categorie'}
      headerActions={
        <button
          type="button"
          className="register-form__submit"
          style={{ background: '#e5e7eb', color: '#111827' }}
          onClick={() => navigate('/admin/catalog/categories')}
        >
          Retour a la liste
        </button>
      }
    >
      {error && <div className="register-form__alert">{error}</div>}
      {message && (
        <div className="register-form__alert" style={{ background: '#ecfdf5', color: '#047857' }}>
          {message}
        </div>
      )}

      {initialLoading ? (
        <p className="muted">Chargement de la categorie...</p>
      ) : (
        <form
          onSubmit={handleSubmit}
          className="register-form-card"
          style={{ display: 'grid', gap: 16 }}
        >
          <label className="register-form__field">
            <span className="register-form__label">Nom</span>
            <input
              className="register-form__input"
              name="name"
              value={form.name}
              onChange={handleChange}
              required
            />
          </label>

          <label className="register-form__field">
            <span className="register-form__label">Slug</span>
            <input
              className="register-form__input"
              name="slug"
              value={form.slug}
              onChange={handleChange}
              placeholder="ex: coupe-cheveux"
            />
          </label>

          <label className="register-form__field">
            <span className="register-form__label">Description</span>
            <textarea
              className="register-form__input"
              name="description"
              rows={4}
              value={form.description}
              onChange={handleChange}
            />
          </label>

          <label className="register-form__field" style={{ flexDirection: 'row', gap: 12 }}>
            <input
              type="checkbox"
              name="isVisible"
              checked={form.isVisible}
              onChange={handleChange}
            />
            <span className="register-form__label">Categorie visible</span>
          </label>

          <button className="register-form__submit" type="submit" disabled={loading}>
            {loading ? 'Enregistrement...' : isEdit ? 'Mettre a jour' : 'Creer'}
          </button>
        </form>
      )}
    </PageContainer>
  );
};
