import { getHttpErrorMessage } from '@/shared/lib/httpClient';
import { useEffect, useState } from 'react';
import { Link, useNavigate, useParams } from 'react-router';
import { useMutation, useQuery, useQueryClient } from '@tanstack/react-query';

import {
  createPrestation,
  fetchAdminPrestation,
  updatePrestation,
  type UpsertPrestationPayload,
} from '@/features/admin/appointments/api';
import type { Prestation } from '@/features/appointments/publicApi';
import { PageContainer } from '@/shared/components/layout/PageContainer';
import { FeedbackMessage, LoadingState } from '@/shared/components/ui/page-state';
import { useDocumentTitle } from '@/shared/hooks/useDocumentTitle';
import { formatEuroInputFromCents } from '@/shared/lib/formatters';
import { adminAppointmentQueryKeys } from '@/features/admin/appointments/queryKeys';
import { useDelayedNavigation } from '@/shared/hooks/useDelayedNavigation';
import {
  parseNonNegativeDecimal,
  parseNonNegativeInteger,
  parseNullablePositiveInteger,
} from '@/shared/lib/parsers';

type PrestationFormState = {
  name: string;
  durationMinutes: string;
  price: string;
};

const emptyForm: PrestationFormState = {
  name: '',
  durationMinutes: '60',
  price: '0',
};

export const PrestationFormPage = () => {
  const { prestationId } = useParams();
  const parsedPrestationId = parseNullablePositiveInteger(prestationId);
  const prestationIdForMutation = parsedPrestationId ?? 0;
  const isEdit = parsedPrestationId !== null;
  const navigate = useNavigate();
  const navigateWithDelay = useDelayedNavigation(600);
  const queryClient = useQueryClient();

  useDocumentTitle(
    isEdit
      ? 'Admin - Modifier un motif de rendez-vous'
      : 'Admin - Nouveau motif de rendez-vous',
  );

  const [form, setForm] = useState<PrestationFormState>(emptyForm);
  const [error, setError] = useState<string | null>(null);
  const [message, setMessage] = useState<string | null>(null);
  const prestationQuery = useQuery<Prestation, Error>({
    queryKey: adminAppointmentQueryKeys.prestation(isEdit ? parsedPrestationId : null),
    queryFn: () => fetchAdminPrestation(prestationIdForMutation),
    enabled: isEdit,
  });
  const saveMutation = useMutation({
    mutationFn: (payload: UpsertPrestationPayload) =>
      isEdit ? updatePrestation(prestationIdForMutation, payload) : createPrestation(payload),
    onSuccess: (response) => {
      void queryClient.invalidateQueries({ queryKey: adminAppointmentQueryKeys.prestations() });
      setMessage(
        response.message ??
          (isEdit ? 'La prestation a bien été mise à jour.' : 'La prestation a bien été créée.'),
      );
      if (!isEdit) setForm(emptyForm);
      navigateWithDelay('/admin/appointments/motifs');
    },
    onError: (err) =>
      setError(getHttpErrorMessage(err, "Impossible d'enregistrer la prestation")),
  });

  useEffect(() => {
    if (prestationQuery.data) populateForm(prestationQuery.data);
  }, [prestationQuery.data]);

  const populateForm = (prestation: Prestation) => {
    setForm({
      name: prestation.name,
      durationMinutes: prestation.durationMinutes.toString(),
      price: formatEuroInputFromCents(prestation.priceCents),
    });
  };

  const handleChange =
    (key: keyof PrestationFormState) => (event: React.ChangeEvent<HTMLInputElement>) => {
      setForm((prev) => ({ ...prev, [key]: event.target.value }));
    };

  const parseForm = (): UpsertPrestationPayload => ({
    name: form.name.trim(),
    durationMinutes: parseNonNegativeInteger(form.durationMinutes, 0),
    price: parseNonNegativeDecimal(form.price, Number.NaN),
  });

  const handleSubmit = async (event: React.FormEvent) => {
    event.preventDefault();

    const payload = parseForm();

    if (!payload.name || payload.durationMinutes <= 0 || Number.isNaN(payload.price)) {
      setError('Veuillez renseigner un nom, une durée et un prix valides.');
      return;
    }

    setError(null);
    setMessage(null);
    saveMutation.mutate(payload);
  };

  return (
    <PageContainer
      size="admin"
      title={isEdit ? 'Modifier un motif de rendez-vous' : 'Nouveau motif de rendez-vous'}
      headerActions={
        <button
          type="button"
          className="catalog-admin-actions__edit"
          onClick={() => navigate('/admin/appointments/motifs')}
        >
          Retour à la liste
        </button>
      }
    >
      <p className="mb-4 text-sm text-stone-600">
        Cette fiche sert uniquement à proposer un motif lors de la réservation d’un rendez-vous.
        Pour gérer le catalogue de services autonome de l’entreprise, utilisez{' '}
        <Link to="/admin/services" className="font-semibold underline">
          Services
        </Link>
        .
      </p>
      {(error || prestationQuery.error) && (
        <FeedbackMessage>
          {error ??
            getHttpErrorMessage(prestationQuery.error, 'Impossible de charger la prestation')}
        </FeedbackMessage>
      )}
      {message && <FeedbackMessage variant="success">{message}</FeedbackMessage>}

      {prestationQuery.isLoading ? (
        <LoadingState>Chargement de la prestation...</LoadingState>
      ) : (
        <form onSubmit={handleSubmit} className="register-form-card form-card-grid">
          <label className="register-form__field">
            <span className="register-form__label">Nom</span>
            <input
              className="register-form__input"
              value={form.name}
              onChange={handleChange('name')}
              required
            />
          </label>

          <label className="register-form__field">
            <span className="register-form__label">Durée (minutes)</span>
            <input
              className="register-form__input"
              type="number"
              min={5}
              step={5}
              inputMode="numeric"
              value={form.durationMinutes}
              onChange={handleChange('durationMinutes')}
              required
            />
          </label>

          <label className="register-form__field">
            <span className="register-form__label">Prix (EUR)</span>
            <input
              className="register-form__input"
              type="number"
              min="0"
              step="0.01"
              value={form.price}
              onChange={handleChange('price')}
            />
          </label>

          <button type="submit" className="register-form__submit" disabled={saveMutation.isPending}>
            {saveMutation.isPending ? 'Enregistrement...' : isEdit ? 'Mettre à jour' : 'Créer'}
          </button>
        </form>
      )}
    </PageContainer>
  );
};
