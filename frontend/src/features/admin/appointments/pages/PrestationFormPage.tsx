import { useEffect, useState } from 'react';
import { Link, useNavigate, useParams } from 'react-router-dom';

import {
  createPrestation,
  fetchAdminPrestation,
  updatePrestation,
  type UpsertPrestationPayload,
} from '@/features/admin/appointments/api';
import { useRequireAdmin } from '@/features/admin/hooks/useRequireAdmin';
import type { Prestation } from '@/features/appointments/types';
import { PageContainer } from '@/shared/components/PageContainer';
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

  useDocumentTitle(isEdit ? 'Admin - Modifier une prestation de rendez-vous' : 'Admin - Nouvelle prestation de rendez-vous');

  const { isAdmin, loading: guardLoading } = useRequireAdmin();
  const [form, setForm] = useState<PrestationFormState>(emptyForm);
  const [loading, setLoading] = useState(false);
  const [initialLoading, setInitialLoading] = useState(false);
  const [error, setError] = useState<string | null>(null);
  const [message, setMessage] = useState<string | null>(null);

  useEffect(() => {
    if (!isEdit || !isAdmin) {
      return;
    }

    const load = async () => {
      setInitialLoading(true);
      setError(null);

      try {
        const prestation = await fetchAdminPrestation(Number(prestationId));
        populateForm(prestation);
      } catch (err: any) {
        setError(err?.message ?? 'Impossible de charger la prestation');
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
        await updatePrestation(Number(prestationId), payload);
        setMessage('Prestation mise à jour.');
      } else {
        await createPrestation(payload);
        setMessage('Prestation créée.');
        setForm(emptyForm);
      }

      setTimeout(() => {
        navigate('/admin/appointments/prestations');
      }, 600);
    } catch (err: any) {
      setError(err?.message ?? 'Impossible d\'enregistrer la prestation');
    } finally {
      setLoading(false);
    }
  };

  if (guardLoading) {
    return (
      <PageContainer title={isEdit ? 'Modifier une prestation de rendez-vous' : 'Nouvelle prestation de rendez-vous'}>
        <p className="muted">Vérification des droits...</p>
      </PageContainer>
    );
  }

  if (!isAdmin) {
    return (
      <PageContainer title={isEdit ? 'Modifier une prestation de rendez-vous' : 'Nouvelle prestation de rendez-vous'}>
        <div className="register-form__alert">Accès restreint aux administrateurs.</div>
      </PageContainer>
    );
  }

  return (
    <PageContainer
      title={isEdit ? 'Modifier une prestation de rendez-vous' : 'Nouvelle prestation de rendez-vous'}
      headerActions={
        <button
          type="button"
          className="register-form__submit"
          style={{ background: '#e5e7eb', color: '#111827' }}
          onClick={() => navigate('/admin/appointments/prestations')}
        >
          Retour à la liste
        </button>
      }
    >
      <p className="mb-4 text-sm text-slate-600">
        Cette fiche sert à la réservation de rendez-vous. Pour gérer le catalogue de services autonome de l’entreprise, utilisez{' '}
        <Link to="/admin/services" className="font-semibold underline">
          Services
        </Link>
        .
      </p>
      {error && <div className="register-form__alert">{error}</div>}
      {message && (
        <div className="register-form__alert" style={{ background: '#ecfdf5', color: '#047857' }}>
          {message}
        </div>
      )}

      {initialLoading ? (
        <p className="muted">Chargement de la prestation...</p>
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
