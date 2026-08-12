import { getHttpErrorMessage } from '@/shared/lib/httpClient';
import { useEffect, useState } from 'react';
import { useNavigate, useParams } from 'react-router';
import { useMutation, useQuery, useQueryClient } from '@tanstack/react-query';

import {
  fetchAdminTrainingCategories,
  fetchAdminTraining,
  saveAdminTraining,
  type TrainingCategoryDto,
  type TrainingFormat,
} from '@/features/trainings/publicApi';
import { PageContainer } from '@/shared/components/layout/PageContainer';
import { FeedbackMessage, LoadingState } from '@/shared/components/ui/page-state';
import { useDocumentTitle } from '@/shared/hooks/useDocumentTitle';
import { formatEuroInputFromCents } from '@/shared/lib/formatters';
import { parseNonNegativeDecimal, parseNullablePositiveInteger } from '@/shared/lib/parsers';
import {
  TrainingFormFields,
  type TrainingFormState,
} from '@/features/admin/trainings/components/TrainingFormFields';
import { adminTrainingQueryKeys } from '@/features/admin/trainings/queryKeys';
import { useDelayedNavigation } from '@/shared/hooks/useDelayedNavigation';

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
  const amount = parseNonNegativeDecimal(value, Number.NaN);

  return Number.isFinite(amount) ? Math.round(amount * 100) : Number.NaN;
};

export const TrainingFormPage = () => {
  const { trainingId } = useParams();
  const parsedTrainingId = parseNullablePositiveInteger(trainingId);
  const safeTrainingId = parsedTrainingId ?? 0;
  const isEdit = parsedTrainingId !== null;
  const navigate = useNavigate();
  const navigateWithDelay = useDelayedNavigation(600);
  const queryClient = useQueryClient();

  useDocumentTitle(isEdit ? 'Admin - Modifier une formation' : 'Admin - Nouvelle formation');

  const [form, setForm] = useState(emptyForm);
  const [error, setError] = useState<string | null>(null);
  const [message, setMessage] = useState<string | null>(null);
  const formQuery = useQuery({
    queryKey: adminTrainingQueryKeys.trainingForm(isEdit ? parsedTrainingId : null),
    queryFn: async () => {
      const [categories, training] = await Promise.all([
        fetchAdminTrainingCategories(),
        isEdit ? fetchAdminTraining(safeTrainingId) : Promise.resolve(null),
      ]);
      return { categories, training };
    },
  });
  const categories: TrainingCategoryDto[] = formQuery.data?.categories ?? [];

  useEffect(() => {
    if (!formQuery.data) return;
    const { categories: categoryItems, training } = formQuery.data;
    if (training) {
          setForm({
            title: training.title,
            shortDescription: training.shortDescription ?? '',
            objective: training.objective ?? '',
            audience: training.audience ?? '',
            category: training.category,
            durationMinutes: training.durationMinutes,
            priceEuros: formatEuroInputFromCents(training.priceCents),
            availableFormats: training.availableFormats,
            isActive: training.isActive,
            roadmap: training.roadmap.map((item) => item.title).join('\n'),
          });
      return;
    }
    const firstCategory = categoryItems[0];
    if (firstCategory) {
      setForm((prev) => ({ ...prev, category: firstCategory.slug }));
    }
  }, [formQuery.data]);

  const handleFormatChange = (format: TrainingFormat, checked: boolean) => {
    setForm((prev) => {
      const nextFormats = checked
        ? Array.from(new Set([...prev.availableFormats, format]))
        : prev.availableFormats.filter((item) => item !== format);

      return { ...prev, availableFormats: nextFormats };
    });
  };

  const saveMutation = useMutation({
    mutationFn: ({ priceCents }: { priceCents: number }) =>
      saveAdminTraining(
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
        isEdit ? safeTrainingId : undefined,
      ),
    onSuccess: (response) => {
      void queryClient.invalidateQueries({ queryKey: adminTrainingQueryKeys.trainings() });
      void queryClient.invalidateQueries({ queryKey: adminTrainingQueryKeys.sessions() });
      void queryClient.invalidateQueries({ queryKey: adminTrainingQueryKeys.enrollments() });
      void queryClient.invalidateQueries({ queryKey: adminTrainingQueryKeys.categories() });
      setMessage(response.message ?? (isEdit ? 'La formation a bien été mise à jour.' : 'La formation a bien été créée.'));
      navigateWithDelay('/admin/trainings');
    },
    onError: (err) => setError(getHttpErrorMessage(err, "Impossible d'enregistrer la formation.")),
  });

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

    if (Number.isNaN(priceCents)) {
      setError('Le prix doit être un montant en euros valide.');
      return;
    }

    setError(null);
    setMessage(null);
    saveMutation.mutate({ priceCents });
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
      {(error || formQuery.error) && (
        <FeedbackMessage>
          {error ?? getHttpErrorMessage(formQuery.error, 'Impossible de charger la formation.')}
        </FeedbackMessage>
      )}
      {message && <FeedbackMessage variant="success">{message}</FeedbackMessage>}

      {formQuery.isLoading ? (
        <LoadingState>Chargement de la formation...</LoadingState>
      ) : (
        <form onSubmit={handleSubmit} className="register-form-card form-card-grid">
          <TrainingFormFields
            form={form}
            categories={categories}
            setForm={setForm}
            onFormatChange={handleFormatChange}
          />
          <button type="submit" className="register-form__submit" disabled={saveMutation.isPending}>
            {saveMutation.isPending ? 'Enregistrement...' : 'Enregistrer'}
          </button>
        </form>
      )}
    </PageContainer>
  );
};
