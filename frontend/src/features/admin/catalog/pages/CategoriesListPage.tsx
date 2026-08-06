import { getHttpErrorMessage } from '@/shared/lib/httpClient';
import { useEffect, useState } from 'react';
import { useMutation, useQuery, useQueryClient } from '@tanstack/react-query';
import { Link } from 'react-router';

import { deleteCategory, fetchAdminCategoriesPage, type CatalogCategory } from '@/features/catalog/adminApi';
import { PageContainer } from '@/shared/components/layout/PageContainer';
import { AdminListState, AdminTableShell } from '@/shared/components/admin/AdminDataView';
import { useDocumentTitle } from '@/shared/hooks/useDocumentTitle';
import { SearchFilter } from '@/shared/components/filters/SearchFilter';
import { useConfirm } from '@/shared/components/ui/confirm';
import { FeedbackMessage, PrimaryLink } from '@/shared/components/ui/page-state';
import { adminCatalogQueryKeys } from '@/features/admin/catalog/queryKeys';
import { PaginationControls } from '@/shared/components/ui/PaginationControls';
import type { PaginatedResult } from '@/shared/types/api';

export const CategoriesListPage = () => {
  useDocumentTitle('Admin - Catégories');

  const [message, setMessage] = useState<string | null>(null);
  const [search, setSearch] = useState('');
  const [page, setPage] = useState(1);
  const confirm = useConfirm();
  const queryClient = useQueryClient();
  const categoriesQuery = useQuery<PaginatedResult<CatalogCategory>, Error>({
    queryKey: [...adminCatalogQueryKeys.categories(), { page, search }],
    queryFn: () => fetchAdminCategoriesPage(page, 10, search),
  });
  const deleteMutation = useMutation({
    mutationFn: deleteCategory,
    onSuccess: (response) => {
      void queryClient.invalidateQueries({ queryKey: adminCatalogQueryKeys.categories() });
      setMessage(response.message ?? 'La catégorie a bien été supprimée.');
    },
  });
  const categories = categoriesQuery.data?.items ?? [];
  const categoriesMeta = categoriesQuery.data?.meta ?? { page, perPage: 10, total: 0, totalPages: 1 };
  const error = categoriesQuery.error
    ? getHttpErrorMessage(categoriesQuery.error, 'Impossible de charger les catégories.')
    : deleteMutation.error
      ? getHttpErrorMessage(deleteMutation.error, 'Impossible de supprimer la catégorie.')
      : null;

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

    setMessage(null);
    deleteMutation.mutate(categoryId);
  };

  useEffect(() => {
    setPage(1);
  }, [search]);

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
          {categoriesMeta.total} catégorie{categoriesMeta.total > 1 ? 's' : ''} affichée
          {categoriesMeta.total > 1 ? 's' : ''}
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
        loading={categoriesQuery.isLoading}
        isEmpty={categories.length === 0}
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
              {categories.map((category) => (
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
        <PaginationControls
          page={categoriesMeta.page}
          total={categoriesMeta.total}
          totalLabel="catégorie"
          totalPages={categoriesMeta.totalPages}
          onPageChange={setPage}
        />
      </AdminListState>
    </PageContainer>
  );
};
