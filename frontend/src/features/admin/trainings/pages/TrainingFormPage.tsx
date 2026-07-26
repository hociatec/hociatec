import { getHttpErrorMessage } from '@/shared/lib/httpClient';
import { useEffect, useState } from 'react';
import { useNavigate, useParams } from 'react-router-dom';

import {
  fetchAdminTrainingCategories,
  fetchAdminTraining,
  saveAdminTraining,
  type TrainingCategoryDto,
  type TrainingFormat,
} from '@/features/trainings/api/trainingsApi';
import { PageContainer } from '@/shared/components/PageContainer';
import { FeedbackMessage, LoadingState } from '@/shared/components/ui/page-state';
import { useDocumentTitle } from '@/shared/hooks/useDocumentTitle';
import {
  TrainingFormFields,
  type TrainingFormState,
} from '@/features/admin/trainings/components/TrainingFormFields';

const emptyForm: TrainingFormState = {
  title: '',
  shortDescription: '',
  objective: '',
  audience: '',
  category: 'bases',
  durationMinutes: 120,
  priceEuros: '90',
  availableFormats: ['onsite', 'remote'] as TrainingFormat[],
  isActive: true,
  roadmap:
    'Vérifier le besoin\nAccompagner sur les actions principales\nRépondre aux questions pratiques',
};

const parseEuroToCents = (value: string) => {
  const normalizedValue = value.replace(',', '.').trim();
  const amount = Number(normalizedValue);

  return Number.isFinite(amount) ? Math.round(amount * 100) : Number.NaN;
};

export const TrainingFormPage = () => {
  const { trainingId } = useParams();
  const isEdit = Boolean(trainingId);
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
      await saveAdminTraining(
        {
          title: form.title,
          shortDescription: form.shortDescription.trim() || null,
          objective: form.objective.trim() || null,
          audience: form.audience.trim() || null,
          category: form.category,
          durationMinutes: form.durationMinutes,
          priceCents,
          availableFormats: form.availableFormats,
          isActive: form.isActive,
          roadmap: form.roadmap
            .split('\n')
            .map((line) => line.trim())
            .filter(Boolean),
        },
        trainingId ? Number(trainingId) : undefined,
      );
      setMessage(isEdit ? 'Formation mise à jour.' : 'Formation créée.');
      setTimeout(() => navigate('/admin/trainings'), 600);
    } catch (err) {
      setError(getHttpErrorMessage(err, "Impossible d'enregistrer la formation."));
    } finally {
      setSaving(false);
    }
  };

  return (
    <PageContainer
      size="admin"
      title={isEdit ? 'Modifier une formation' : 'Nouvelle formation'}
      headerActions={
        <button
          type="button"
          className="catalog-admin-actions__edit"
          onClick={() => navigate('/admin/trainings')}
        >
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
          <TrainingFormFields
            form={form}
            categories={categories}
            setForm={setForm}
            onFormatChange={handleFormatChange}
          />
          <button type="submit" className="register-form__submit" disabled={saving}>
            {saving ? 'Enregistrement...' : 'Enregistrer'}
          </button>
        </form>
      )}
    </PageContainer>
  );
};
