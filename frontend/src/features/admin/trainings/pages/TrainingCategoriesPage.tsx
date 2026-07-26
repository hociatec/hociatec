import { getHttpErrorMessage } from '@/shared/lib/httpClient';
import { useEffect, useState } from 'react';
import { Link } from 'react-router-dom';

import {
  deleteAdminTrainingCategory,
  fetchAdminTrainingCategories,
  saveAdminTrainingCategory,
  type TrainingCategoryDto,
} from '@/features/trainings/api/trainingsApi';
import { PageContainer } from '@/shared/components/PageContainer';
import { AdminListState, AdminTableShell } from '@/shared/components/admin/AdminDataView';
import { useConfirm } from '@/shared/components/ui/confirm';
import { FeedbackMessage } from '@/shared/components/ui/page-state';
import { useDocumentTitle } from '@/shared/hooks/useDocumentTitle';

const emptyForm = {
  id: null as number | null,
  name: '',
  slug: '',
  position: 0,
  isActive: true,
};

export const TrainingCategoriesPage = () => {
  useDocumentTitle('Admin - Catégories de formation');

  const [categories, setCategories] = useState<TrainingCategoryDto[]>([]);
  const [form, setForm] = useState(emptyForm);
  const [loading, setLoading] = useState(true);
  const [saving, setSaving] = useState(false);
  const [error, setError] = useState<string | null>(null);
  const [message, setMessage] = useState<string | null>(null);
  const confirm = useConfirm();

  const load = async () => {
    setLoading(true);
    setError(null);

    try {
      setCategories(await fetchAdminTrainingCategories());
    } catch (err) {
      setError(getHttpErrorMessage(err, 'Impossible de charger les catégories.'));
    } finally {
      setLoading(false);
    }
  };

  useEffect(() => {
    void load();
  }, []);

  const edit = (category: TrainingCategoryDto) => {
    setForm({
      id: category.id,
      name: category.name,
      slug: category.slug,
      position: category.position,
      isActive: category.isActive,
    });
    setMessage(null);
    setError(null);
  };

  const reset = () => setForm(emptyForm);

  const handleSubmit = async (event: React.FormEvent) => {
    event.preventDefault();

    if (!form.name.trim()) {
      setError('Le nom est requis.');
      return;
    }

    setSaving(true);
    setError(null);
    setMessage(null);

    try {
      await saveAdminTrainingCategory({
        name: form.name,
        slug: form.slug.trim() || undefined,
        position: form.position,
        isActive: form.isActive,
      }, form.id ?? undefined);
      await load();
      setMessage(form.id ? 'Catégorie mise à jour.' : 'Catégorie créée.');
      reset();
    } catch (err) {
      setError(getHttpErrorMessage(err, "Impossible d'enregistrer la catégorie."));
    } finally {
      setSaving(false);
    }
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

    setError(null);
    setMessage(null);

    try {
      await deleteAdminTrainingCategory(category.id);
      await load();
      setMessage('Catégorie supprimée.');
      if (form.id === category.id) reset();
    } catch (err) {
      setError(getHttpErrorMessage(err, 'Impossible de supprimer la catégorie.'));
    }
  };

  return (
    <PageContainer size="admin"
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
          <h2 className="text-lg font-semibold text-brand-900">{form.id ? 'Modifier la catégorie' : 'Nouvelle catégorie'}</h2>
          <label className="register-form__field">
            <span>Nom</span>
            <input value={form.name} onChange={(event) => setForm((prev) => ({ ...prev, name: event.target.value }))} required />
          </label>
          <label className="register-form__field">
            <span>Slug</span>
            <input value={form.slug} onChange={(event) => setForm((prev) => ({ ...prev, slug: event.target.value }))} placeholder="auto si vide" />
          </label>
          <label className="register-form__field">
            <span>Ordre d’affichage</span>
            <input type="number" value={form.position} onChange={(event) => setForm((prev) => ({ ...prev, position: Number(event.target.value) }))} />
          </label>
          <label className="booking__checkbox">
            <input type="checkbox" checked={form.isActive} onChange={(event) => setForm((prev) => ({ ...prev, isActive: event.target.checked }))} />
            Catégorie visible dans les filtres
          </label>
          <div className="flex flex-wrap gap-3">
            <button type="submit" className="register-form__submit" disabled={saving}>
              {saving ? 'Enregistrement...' : 'Enregistrer'}
            </button>
            {form.id ? (
              <button type="button" className="catalog-admin-actions__edit" onClick={reset}>
                Annuler
              </button>
            ) : null}
          </div>
        </form>

        <AdminListState
          loading={loading}
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
                    <td><strong>{category.name}</strong></td>
                    <td>{category.slug}</td>
                    <td>{category.position}</td>
                    <td>{category.isActive ? 'Visible' : 'Masquée'}</td>
                    <td>
                      <div className="catalog-admin-actions">
                        <button type="button" className="catalog-admin-actions__edit" onClick={() => edit(category)}>
                          Modifier
                        </button>
                        <button type="button" className="catalog-admin-actions__delete" onClick={() => void handleDelete(category)}>
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
      </div>
    </PageContainer>
  );
};
