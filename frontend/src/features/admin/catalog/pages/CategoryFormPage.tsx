import { getHttpErrorMessage } from '@/shared/lib/httpClient';
import { useEffect, useState } from 'react';
import { useNavigate, useParams } from 'react-router';
import { useMutation, useQuery, useQueryClient } from '@tanstack/react-query';

import {
  createCategory,
  fetchAdminCategory,
  updateCategory,
  type CatalogCategory,
  type UpsertCategoryPayload,
} from '@/features/catalog/adminApi';
import { PageContainer } from '@/shared/components/layout/PageContainer';
import { FeedbackMessage, LoadingState } from '@/shared/components/ui/page-state';
import { useDocumentTitle } from '@/shared/hooks/useDocumentTitle';
import { adminCatalogQueryKeys } from '@/features/admin/catalog/queryKeys';

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
  const isEdit = Boolean(categoryId);
  const navigate = useNavigate();
  const queryClient = useQueryClient();

  useDocumentTitle(isEdit ? 'Admin - Modifier une catégorie' : 'Admin - Nouvelle catégorie');

  const [form, setForm] = useState<CategoryFormState>(emptyForm);
  const [error, setError] = useState<string | null>(null);
  const [message, setMessage] = useState<string | null>(null);
  const categoryQuery = useQuery<CatalogCategory, Error>({
    queryKey: adminCatalogQueryKeys.category(categoryId ? Number(categoryId) : null),
    queryFn: () => fetchAdminCategory(Number(categoryId)),
    enabled: isEdit,
  });
  const saveMutation = useMutation({
    mutationFn: (payload: UpsertCategoryPayload) =>
      isEdit ? updateCategory(Number(categoryId), payload) : createCategory(payload),
    onSuccess: (response) => {
      void queryClient.invalidateQueries({ queryKey: adminCatalogQueryKeys.categories() });
      setMessage(
        response.message ??
          (isEdit ? 'La catégorie a bien été mise à jour.' : 'La catégorie a bien été créée.'),
      );
      if (!isEdit) setForm(emptyForm);
      setTimeout(() => {
        navigate('/admin/catalog/categories');
      }, 600);
    },
    onError: (err) =>
      setError(getHttpErrorMessage(err, "Impossible d'enregistrer la catégorie.")),
  });

  useEffect(() => {
    if (categoryQuery.data) populateForm(categoryQuery.data);
  }, [categoryQuery.data]);

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
        const shouldSyncSlug = prev.slug.trim() === '' || prev.slug === slugify(prev.name);
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
      setError('Le nom de la catégorie est requis.');
      return;
    }

    setError(null);
    setMessage(null);
    saveMutation.mutate(payload);
  };

  return (
    <PageContainer
      size="admin"
      title={isEdit ? 'Modifier une catégorie' : 'Nouvelle catégorie'}
      headerActions={
        <button
          type="button"
          className="catalog-admin-actions__edit"
          onClick={() => navigate('/admin/catalog/categories')}
        >
          Retour à la liste
        </button>
      }
    >
      {(error || categoryQuery.error) && (
        <FeedbackMessage>
          {error ??
            getHttpErrorMessage(categoryQuery.error, 'Impossible de charger la catégorie.')}
        </FeedbackMessage>
      )}
      {message && <FeedbackMessage variant="success">{message}</FeedbackMessage>}

      {categoryQuery.isLoading ? (
        <LoadingState>Chargement de la catégorie...</LoadingState>
      ) : (
        <form onSubmit={handleSubmit} className="register-form-card form-card-grid">
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

          <label className="booking__checkbox">
            <input
              type="checkbox"
              name="isVisible"
              checked={form.isVisible}
              onChange={handleChange}
            />
            Catégorie visible
          </label>

          <button className="register-form__submit" type="submit" disabled={saveMutation.isPending}>
            {saveMutation.isPending ? 'Enregistrement...' : isEdit ? 'Mettre à jour' : 'Créer'}
          </button>
        </form>
      )}
    </PageContainer>
  );
};
