import { getHttpErrorMessage } from '@/shared/lib/httpClient';
import { useEffect, useState } from 'react';
import { Link, useNavigate, useParams } from 'react-router';

import {
  createPrestation,
  fetchAdminPrestation,
  updatePrestation,
  type UpsertPrestationPayload,
} from '@/features/admin/appointments/api';
import type { Prestation } from '@/features/appointments/types/appointments';
import { PageContainer } from '@/shared/components/layout/PageContainer';
import { FeedbackMessage, LoadingState } from '@/shared/components/ui/page-state';
import { useDocumentTitle } from '@/shared/hooks/useDocumentTitle';

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
  const isEdit = Boolean(prestationId);
  const navigate = useNavigate();

  useDocumentTitle(
    isEdit
      ? 'Admin - Modifier un motif de rendez-vous'
      : 'Admin - Nouveau motif de rendez-vous',
  );

  const [form, setForm] = useState<PrestationFormState>(emptyForm);
  const [loading, setLoading] = useState(false);
  const [initialLoading, setInitialLoading] = useState(false);
  const [error, setError] = useState<string | null>(null);
  const [message, setMessage] = useState<string | null>(null);

  useEffect(() => {
    if (!isEdit) {
      return;
    }

    const load = async () => {
      setInitialLoading(true);
      setError(null);

      try {
        const prestation = await fetchAdminPrestation(Number(prestationId));
        populateForm(prestation);
      } catch (err) {
        setError(getHttpErrorMessage(err, 'Impossible de charger la prestation'));
      } finally {
        setInitialLoading(false);
      }
    };

    void load();
  }, [isEdit, prestationId]);

  const populateForm = (prestation: Prestation) => {
    setForm({
      name: prestation.name,
      durationMinutes: prestation.durationMinutes.toString(),
      price: (prestation.priceCents / 100).toFixed(2),
    });
  };

  const handleChange =
    (key: keyof PrestationFormState) => (event: React.ChangeEvent<HTMLInputElement>) => {
      setForm((prev) => ({ ...prev, [key]: event.target.value }));
    };

  const parseForm = (): UpsertPrestationPayload => ({
    name: form.name.trim(),
    durationMinutes: Number.parseInt(form.durationMinutes, 10) || 0,
    price: form.price.replace(',', '.'),
  });

  const handleSubmit = async (event: React.FormEvent) => {
    event.preventDefault();

    const payload = parseForm();

    if (!payload.name || payload.durationMinutes <= 0) {
      setError('Veuillez renseigner un nom et une durée valides.');
      return;
    }

    setLoading(true);
    setError(null);
    setMessage(null);

    try {
      if (isEdit) {
        const response = await updatePrestation(Number(prestationId), payload);
        setMessage(response.message ?? 'La prestation a bien été mise à jour.');
      } else {
        const response = await createPrestation(payload);
        setMessage(response.message ?? 'La prestation a bien été créée.');
        setForm(emptyForm);
      }

      setTimeout(() => {
        navigate('/admin/appointments/motifs');
      }, 600);
    } catch (err) {
      setError(getHttpErrorMessage(err, "Impossible d'enregistrer la prestation"));
    } finally {
      setLoading(false);
    }
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
      {error && <FeedbackMessage>{error}</FeedbackMessage>}
      {message && <FeedbackMessage variant="success">{message}</FeedbackMessage>}

      {initialLoading ? (
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

          <button type="submit" className="register-form__submit" disabled={loading}>
            {loading ? 'Enregistrement...' : isEdit ? 'Mettre à jour' : 'Créer'}
          </button>
        </form>
      )}
    </PageContainer>
  );
};
