import { getHttpErrorMessage } from '@/shared/lib/httpClient';
import { useEffect, useMemo, useState } from 'react';
import { useNavigate, useParams } from 'react-router-dom';

import {
  fetchAdminTrainingCategories,
  fetchAdminTraining,
  saveAdminTraining,
  type TrainingCategoryDto,
  type TrainingFormat,
} from '@/features/trainings/api';
import { PageContainer } from '@/shared/components/PageContainer';
import { FeedbackMessage, LoadingState } from '@/shared/components/ui/page-state';
import { useDocumentTitle } from '@/shared/hooks/useDocumentTitle';

const emptyForm = {
  title: '',
  shortDescription: '',
  objective: '',
  audience: '',
  category: 'bases',
  durationMinutes: 120,
  priceEuros: '90',
  availableFormats: ['onsite', 'remote'] as TrainingFormat[],
  isActive: true,
  roadmap: 'Vérifier le besoin\nAccompagner sur les actions principales\nRépondre aux questions pratiques',
};

const parseEuroToCents = (value: string) => {
  const normalizedValue = value.replace(',', '.').trim();
  const amount = Number(normalizedValue);

  return Number.isFinite(amount) ? Math.round(amount * 100) : Number.NaN;
};

export const TrainingFormPage = () => {
  const { trainingId } = useParams();
  const isEdit = useMemo(() => Boolean(trainingId), [trainingId]);
  const navigate = useNavigate();

  useDocumentTitle(isEdit ? 'Admin - Modifier une formation' : 'Admin - Nouvelle formation');

  const [form, setForm] = useState(emptyForm);
  const [categories, setCategories] = useState<TrainingCategoryDto[]>([]);
  const [initialLoading, setInitialLoading] = useState(false);
  const [saving, setSaving] = useState(false);
  const [error, setError] = useState<string | null>(null);
  const [message, setMessage] = useState<string | null>(null);

  useEffect(() => {
    const load = async () => {
      setInitialLoading(true);
      setError(null);

      try {
        const [categoryItems, training] = await Promise.all([
          fetchAdminTrainingCategories(),
          isEdit && trainingId ? fetchAdminTraining(Number(trainingId)) : Promise.resolve(null),
        ]);
        setCategories(categoryItems);

        if (training) {
          setForm({
            title: training.title,
            shortDescription: training.shortDescription ?? '',
            objective: training.objective ?? '',
            audience: training.audience ?? '',
            category: training.category,
            durationMinutes: training.durationMinutes,
            priceEuros: String(training.priceCents / 100),
            availableFormats: training.availableFormats,
            isActive: training.isActive,
            roadmap: training.roadmap.map((item) => item.title).join('\n'),
          });
        } else if (categoryItems.length > 0) {
          setForm((prev) => ({ ...prev, category: categoryItems[0].slug }));
        }
      } catch (err) {
        setError(getHttpErrorMessage(err, 'Impossible de charger la formation.'));
      } finally {
        setInitialLoading(false);
      }
    };

    void load();
  }, [isEdit, trainingId]);

  const handleFormatChange = (format: TrainingFormat, checked: boolean) => {
    setForm((prev) => {
      const nextFormats = checked
        ? Array.from(new Set([...prev.availableFormats, format]))
        : prev.availableFormats.filter((item) => item !== format);

      return { ...prev, availableFormats: nextFormats };
    });
  };

  const handleSubmit = async (event: React.FormEvent) => {
    event.preventDefault();

    if (!form.title.trim()) {
      setError('Le titre est requis.');
      return;
    }

    if (form.availableFormats.length === 0) {
      setError('Sélectionnez au moins un format.');
      return;
    }

    const priceCents = parseEuroToCents(form.priceEuros);

    if (!Number.isFinite(priceCents) || priceCents < 0) {
      setError('Le prix doit être un montant en euros valide.');
      return;
    }

    setSaving(true);
    setError(null);
    setMessage(null);

    try {
      await saveAdminTraining({
        title: form.title,
        shortDescription: form.shortDescription.trim() || null,
        objective: form.objective.trim() || null,
        audience: form.audience.trim() || null,
        category: form.category,
        durationMinutes: form.durationMinutes,
        priceCents,
        availableFormats: form.availableFormats,
        isActive: form.isActive,
        roadmap: form.roadmap.split('\n').map((line) => line.trim()).filter(Boolean),
      }, trainingId ? Number(trainingId) : undefined);
      setMessage(isEdit ? 'Formation mise à jour.' : 'Formation créée.');
      setTimeout(() => navigate('/admin/trainings'), 600);
    } catch (err) {
      setError(getHttpErrorMessage(err, "Impossible d'enregistrer la formation."));
    } finally {
      setSaving(false);
    }
  };

  return (
    <PageContainer size="admin"
      title={isEdit ? 'Modifier une formation' : 'Nouvelle formation'}
      headerActions={
        <button type="button" className="catalog-admin-actions__edit" onClick={() => navigate('/admin/trainings')}>
          Retour aux formations
        </button>
      }
    >
      {error && <FeedbackMessage>{error}</FeedbackMessage>}
      {message && <FeedbackMessage variant="success">{message}</FeedbackMessage>}

      {initialLoading ? (
        <LoadingState>Chargement de la formation...</LoadingState>
      ) : (
        <form onSubmit={handleSubmit} className="register-form-card form-card-grid">
          <label className="register-form__field">
            <span>Titre</span>
            <input value={form.title} onChange={(event) => setForm((prev) => ({ ...prev, title: event.target.value }))} required />
          </label>
          <label className="register-form__field">
            <span>Description courte</span>
            <textarea value={form.shortDescription} onChange={(event) => setForm((prev) => ({ ...prev, shortDescription: event.target.value }))} />
          </label>
          <label className="register-form__field">
            <span>Objectif</span>
            <textarea value={form.objective} onChange={(event) => setForm((prev) => ({ ...prev, objective: event.target.value }))} />
          </label>
          <label className="register-form__field">
            <span>Public concerné</span>
            <textarea value={form.audience} onChange={(event) => setForm((prev) => ({ ...prev, audience: event.target.value }))} />
          </label>
          <label className="register-form__field">
            <span>Catégorie</span>
            <select value={form.category} onChange={(event) => setForm((prev) => ({ ...prev, category: event.target.value }))}>
              {categories.map((category) => (
                <option key={category.id} value={category.slug}>{category.name}</option>
              ))}
            </select>
          </label>
          <div className="grid gap-4 md:grid-cols-2">
            <label className="register-form__field">
              <span>Durée en minutes</span>
              <input type="number" min={1} value={form.durationMinutes} onChange={(event) => setForm((prev) => ({ ...prev, durationMinutes: Number(event.target.value) }))} />
            </label>
            <label className="register-form__field">
              <span>Prix en euros</span>
              <input
                type="number"
                min={0}
                step="0.01"
                value={form.priceEuros}
                onChange={(event) => setForm((prev) => ({ ...prev, priceEuros: event.target.value }))}
              />
            </label>
          </div>
          <fieldset className="register-form__field">
            <span>Formats disponibles</span>
            <label className="booking__checkbox">
              <input type="checkbox" checked={form.availableFormats.includes('onsite')} onChange={(event) => handleFormatChange('onsite', event.target.checked)} />
              Présentiel
            </label>
            <label className="booking__checkbox">
              <input type="checkbox" checked={form.availableFormats.includes('remote')} onChange={(event) => handleFormatChange('remote', event.target.checked)} />
              Distanciel
            </label>
          </fieldset>
          <label className="register-form__field">
            <span>Feuille de route, une étape par ligne</span>
            <textarea rows={6} value={form.roadmap} onChange={(event) => setForm((prev) => ({ ...prev, roadmap: event.target.value }))} />
          </label>
          <label className="booking__checkbox">
            <input type="checkbox" checked={form.isActive} onChange={(event) => setForm((prev) => ({ ...prev, isActive: event.target.checked }))} />
            Formation publiée
          </label>
          <button type="submit" className="register-form__submit" disabled={saving}>
            {saving ? 'Enregistrement...' : 'Enregistrer'}
          </button>
        </form>
      )}
    </PageContainer>
  );
};
