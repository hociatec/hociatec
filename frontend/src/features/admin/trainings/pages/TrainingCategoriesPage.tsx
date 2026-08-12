import { getHttpErrorMessage } from '@/shared/lib/httpClient';
import { useState } from 'react';
import { useMutation, useQuery, useQueryClient } from '@tanstack/react-query';
import { Link, useSearchParams } from 'react-router';

import {
  deleteAdminTrainingCategory,
  fetchAdminTrainingCategoriesPage,
  saveAdminTrainingCategory,
  type TrainingCategoryDto,
} from '@/features/trainings/publicApi';
import { PageContainer } from '@/shared/components/layout/PageContainer';
import { AdminListState, AdminTableShell } from '@/shared/components/admin/AdminDataView';
import { useConfirm } from '@/shared/components/ui/confirm';
import { FeedbackMessage } from '@/shared/components/ui/page-state';
import { useDocumentTitle } from '@/shared/hooks/useDocumentTitle';
import { adminTrainingQueryKeys } from '@/features/admin/trainings/queryKeys';
import { omitUndefinedProperties } from '@/shared/lib/object';
import { PaginationControls } from '@/shared/components/ui/PaginationControls';
import type { PaginatedResult } from '@/shared/types/api';
import { parseNonNegativeInteger } from '@/shared/lib/parsers';
import { parseNullablePositiveInteger } from '@/shared/lib/parsers';
import { useEffect } from 'react';

const emptyForm = {
  id: null as number | null,
  name: '',
  slug: '',
  position: 0,
  isActive: true,
};

export const TrainingCategoriesPage = () => {
  useDocumentTitle('Admin - Catégories de formation');

  const [form, setForm] = useState(emptyForm);
  const [message, setMessage] = useState<string | null>(null);
  const [formError, setFormError] = useState<string | null>(null);
  const [searchParams, setSearchParams] = useSearchParams();
  const [page, setPage] = useState(parseNullablePositiveInteger(searchParams.get('page')) ?? 1);
  const confirm = useConfirm();
  const queryClient = useQueryClient();
  const categoriesQuery = useQuery<PaginatedResult<TrainingCategoryDto>, Error>({
    queryKey: [...adminTrainingQueryKeys.categories(), { page }],
    queryFn: () => fetchAdminTrainingCategoriesPage(page, 10),
  });
  const invalidateCategories = () => {
    void queryClient.invalidateQueries({ queryKey: adminTrainingQueryKeys.categories() });
    void queryClient.invalidateQueries({ queryKey: adminTrainingQueryKeys.trainings() });
  };
  const saveMutation = useMutation({
    mutationFn: ({ id, payload }: { id?: number; payload: Parameters<typeof saveAdminTrainingCategory>[0] }) =>
      saveAdminTrainingCategory(payload, id),
    onSuccess: (response) => {
      invalidateCategories();
      setMessage(
        response.message ?? (form.id ? 'La catégorie a bien été mise à jour.' : 'La catégorie a bien été créée.'),
      );
      reset();
    },
  });
  const deleteMutation = useMutation({
    mutationFn: deleteAdminTrainingCategory,
    onSuccess: (response, categoryId) => {
      invalidateCategories();
      setMessage(response.message ?? 'La catégorie a bien été supprimée.');
      if (form.id === categoryId) reset();
    },
  });
  const categories = categoriesQuery.data?.items ?? [];
  const pagination = categoriesQuery.data?.meta ?? { page, perPage: 10, total: 0, totalPages: 1 };
  const error = categoriesQuery.error
    ? getHttpErrorMessage(categoriesQuery.error, 'Impossible de charger les catégories.')
    : saveMutation.error
      ? getHttpErrorMessage(saveMutation.error, "Impossible d'enregistrer la catégorie.")
      : deleteMutation.error
        ? getHttpErrorMessage(deleteMutation.error, 'Impossible de supprimer la catégorie.')
        : formError;

  useEffect(() => {
    const next = new URLSearchParams();
    if (page > 1) {
      next.set('page', String(page));
    }
    setSearchParams(next, { replace: true });
  }, [page, setSearchParams]);

  const edit = (category: TrainingCategoryDto) => {
    setForm({
      id: category.id,
      name: category.name,
      slug: category.slug,
      position: category.position,
      isActive: category.isActive,
    });
    setMessage(null);
    setFormError(null);
  };

  const reset = () => setForm(emptyForm);

  const handleSubmit = async (event: React.FormEvent) => {
    event.preventDefault();

    if (!form.name.trim()) {
      setFormError('Le nom est requis.');
      return;
    }

    setFormError(null);
    setMessage(null);
    saveMutation.mutate({
      ...(form.id !== null && form.id !== undefined ? { id: form.id } : {}),
      payload: omitUndefinedProperties({
        name: form.name,
        slug: form.slug.trim() || undefined,
        position: form.position,
        isActive: form.isActive,
      }),
    });
  };

  const handleDelete = async (category: TrainingCategoryDto) => {
    const confirmed = await confirm({
      title: 'Supprimer la catégorie',
      description: `Supprimer la catégorie "${category.name}" ?`,
      confirmLabel: 'Supprimer',
      cancelLabel: 'Annuler',
    });

    if (!confirmed) {
      return;
    }

    setMessage(null);
    deleteMutation.mutate(category.id);
  };

  return (
    <PageContainer
      size="admin"
      title="Catégories de formation"
      headerActions={
        <Link to="/admin/trainings" className="catalog-admin-actions__edit">
          Formations
        </Link>
      }
    >
      {error && <FeedbackMessage>{error}</FeedbackMessage>}
      {message && <FeedbackMessage variant="success">{message}</FeedbackMessage>}

      <div className="grid gap-6 lg:grid-cols-[360px_1fr]">
        <form onSubmit={handleSubmit} className="register-form-card form-card-grid">
          <h2 className="text-lg font-semibold text-brand-900">
            {form.id ? 'Modifier la catégorie' : 'Nouvelle catégorie'}
          </h2>
          <label className="register-form__field">
            <span>Nom</span>
            <input
              value={form.name}
              onChange={(event) => setForm((prev) => ({ ...prev, name: event.target.value }))}
              required
            />
          </label>
          <label className="register-form__field">
            <span>Slug</span>
            <input
              value={form.slug}
              onChange={(event) => setForm((prev) => ({ ...prev, slug: event.target.value }))}
              placeholder="auto si vide"
            />
          </label>
          <label className="register-form__field">
                <span>Ordre d’affichage</span>
            <input
              type="number"
              value={form.position}
              onChange={(event) =>
                setForm((prev) => ({
                  ...prev,
                  position: parseNonNegativeInteger(event.target.value, 0),
                }))
              }
            />
          </label>
          <label className="booking__checkbox">
            <input
              type="checkbox"
              checked={form.isActive}
              onChange={(event) => setForm((prev) => ({ ...prev, isActive: event.target.checked }))}
            />
            Catégorie visible dans les filtres
          </label>
          <div className="flex flex-wrap gap-3">
            <button type="submit" className="register-form__submit" disabled={saveMutation.isPending}>
              {saveMutation.isPending ? 'Enregistrement...' : 'Enregistrer'}
            </button>
            {form.id ? (
              <button type="button" className="catalog-admin-actions__edit" onClick={reset}>
                Annuler
              </button>
            ) : null}
          </div>
        </form>

        <AdminListState
          loading={categoriesQuery.isLoading}
          isEmpty={categories.length === 0}
          loadingLabel="Chargement des catégories..."
          emptyLabel="Aucune catégorie."
        >
          <AdminTableShell>
            <table className="catalog-admin-table">
              <thead>
                <tr>
                  <th>Nom</th>
                  <th>Slug</th>
                  <th>Ordre</th>
                  <th>Statut</th>
                  <th>Actions</th>
                </tr>
              </thead>
              <tbody>
                {categories.map((category) => (
                  <tr key={category.id}>
                    <td>
                      <strong>{category.name}</strong>
                    </td>
                    <td>{category.slug}</td>
                    <td>{category.position}</td>
                    <td>{category.isActive ? 'Visible' : 'Masquée'}</td>
                    <td>
                      <div className="catalog-admin-actions">
                        <button
                          type="button"
                          className="catalog-admin-actions__edit"
                          onClick={() => edit(category)}
                        >
                          Modifier
                        </button>
                        <button
                          type="button"
                          className="catalog-admin-actions__delete"
                          onClick={() => void handleDelete(category)}
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
          <PaginationControls
            page={pagination.page}
            total={pagination.total}
            totalLabel="catégorie"
            totalPages={pagination.totalPages}
            onPageChange={setPage}
          />
        </AdminListState>
      </div>
    </PageContainer>
  );
};
