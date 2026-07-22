import { getHttpErrorMessage } from '@/shared/lib/httpClient';
import { type ChangeEvent, useEffect, useState } from 'react';
import { useNavigate, useParams } from 'react-router-dom';

import { createAdminQuoteService, fetchAdminQuoteService, updateAdminQuoteService } from '@/features/quotes/api';
import { PageContainer } from '@/shared/components/PageContainer';
import { FeedbackMessage, LoadingState } from '@/shared/components/ui/page-state';
import { useDocumentTitle } from '@/shared/hooks/useDocumentTitle';

type ServiceFormState = {
  title: string;
  description: string;
  unit: string;
  durationValue: string;
  durationUnit: 'hour' | 'day';
  price: string;
  vatRate: string;
};

type ServicePayload = {
  title: string;
  description: string;
  unit: string;
  durationValue: number | '';
  durationUnit: 'hour' | 'day' | '';
  price: number;
  vatRate: number;
};

const BILLING_MODE_OPTIONS = [
  { value: 'prix fixe', label: 'Prix fixe' },
  { value: 'heure', label: 'Par heure' },
  { value: 'jour', label: 'Par jour' },
  { value: 'intervention', label: 'Par intervention' },
  { value: 'audit', label: 'Par audit' },
  { value: 'installation', label: 'Par installation' },
  { value: 'maintenance', label: 'Par maintenance' },
] as const;

const emptyForm: ServiceFormState = {
  title: '',
  description: '',
  unit: 'prix fixe',
  durationValue: '',
  durationUnit: 'hour',
  price: '0',
  vatRate: '20',
};

export const ServiceFormPage = () => {
  const params = useParams<{ serviceId?: string }>();
  const parsedServiceId = params.serviceId ? Number.parseInt(params.serviceId, 10) : NaN;
  const serviceId = Number.isNaN(parsedServiceId) ? null : parsedServiceId;
  const isEdit = serviceId !== null;
  useDocumentTitle(isEdit ? 'Admin - Modifier un service' : 'Admin - Nouveau service');
  const navigate = useNavigate();

  const [form, setForm] = useState<ServiceFormState>(emptyForm);
  const [initialLoading, setInitialLoading] = useState(false);
  const [saving, setSaving] = useState(false);
  const [error, setError] = useState<string | null>(null);
  const [message, setMessage] = useState<string | null>(null);

  useEffect(() => {
    if (!isEdit || serviceId === null) {
      return;
    }
    setInitialLoading(true);
    setError(null);
    void fetchAdminQuoteService(serviceId)
      .then((svc) => {
        setForm({
          title: svc?.title ?? '',
          description: svc?.description ?? '',
          unit: svc?.unit ?? 'prix fixe',
          durationValue: svc?.durationValue ? String(svc.durationValue) : '',
          durationUnit: svc?.durationUnit === 'day' ? 'day' : 'hour',
          price: svc ? (svc.priceCents / 100).toFixed(2) : '0',
          vatRate: svc ? String(svc.vatRate ?? 0) : '0',
        });
      })
      .catch((e) => setError(getHttpErrorMessage(e, 'Chargement impossible.')))
      .finally(() => setInitialLoading(false));
  }, [isEdit, serviceId]);

  const handleChange =
    (field: keyof ServiceFormState) =>
      (event: ChangeEvent<HTMLInputElement | HTMLTextAreaElement>) => {
        setForm((prev) => ({ ...prev, [field]: event.target.value }));
      };

  const parseNumberField = (value: string): number => {
    const normalized = value.replace(',', '.').trim();
    if (normalized === '') {
      return Number.NaN;
    }
    const parsed = Number.parseFloat(normalized);
    return Number.isFinite(parsed) ? parsed : Number.NaN;
  };

  const buildPayload = (): ServicePayload | null => {
    const title = form.title.trim();
    if (!title) {
      setError('Veuillez renseigner un titre.');
      return null;
    }

    const price = parseNumberField(form.price);
    if (Number.isNaN(price) || price < 0) {
      setError('Veuillez renseigner un prix HT valide.');
      return null;
    }

    const vatRate = form.vatRate.trim() === '' ? 0 : parseNumberField(form.vatRate);
    if (Number.isNaN(vatRate) || vatRate < 0) {
      setError('Veuillez renseigner un taux de TVA valide.');
      return null;
    }

    const description = form.description.trim();
    const unit = form.unit.trim().toLowerCase();
    const durationValue = form.durationValue.trim();

    if (!BILLING_MODE_OPTIONS.some((option) => option.value === unit)) {
      setError('Veuillez sélectionner un mode de facturation valide.');
      return null;
    }

    if (durationValue !== '') {
      const parsedDurationValue = Number.parseInt(durationValue, 10);
      if (!Number.isFinite(parsedDurationValue) || parsedDurationValue <= 0) {
        setError('Veuillez renseigner une durée estimée valide.');
        return null;
      }
    }

    const parsedDurationValue = durationValue === '' ? '' : Number.parseInt(durationValue, 10);
    const parsedDurationUnit: ServicePayload['durationUnit'] = durationValue === '' ? '' : form.durationUnit;

    return {
      title,
      description,
      unit,
      durationValue: parsedDurationValue,
      durationUnit: parsedDurationUnit,
      price,
      vatRate,
    };
  };

  const handleSubmit = async (event: React.FormEvent) => {
    event.preventDefault();

    setError(null);
    setMessage(null);

    const payload = buildPayload();
    if (!payload) {
      return;
    }

    setSaving(true);
    try {
      if (isEdit && serviceId !== null) {
        await updateAdminQuoteService(serviceId, payload);
        setMessage('Service mis à jour.');
      } else {
        await createAdminQuoteService(payload);
        setMessage('Service créé.');
      }
      navigate('/admin/services');
    } catch (e) {
      setError(getHttpErrorMessage(e, 'Échec de sauvegarde.'));
    } finally {
      setSaving(false);
    }
  };

  return (
    <PageContainer size="admin"
      title={isEdit ? 'Modifier un service' : 'Nouveau service'}
      headerActions={
        <button
          type="button"
          className="catalog-admin-actions__edit"
          onClick={() => navigate('/admin/services')}
        >
          Retour à la liste
        </button>
      }
    >
      <p className="mb-4 text-sm text-stone-600">
        Renseignez ici les informations complètes du service, y compris sa durée estimée lorsqu’elle est connue.
      </p>
      {error && <FeedbackMessage>{error}</FeedbackMessage>}
      {message && <FeedbackMessage variant="success">{message}</FeedbackMessage>}

      {initialLoading && isEdit ? (
        <LoadingState>Chargement du service...</LoadingState>
      ) : (
        <form
          onSubmit={handleSubmit}
          className="register-form-card form-card-grid"
        >
          <label className="register-form__field">
            <span className="register-form__label">Titre</span>
            <input
              className="register-form__input"
              placeholder="Intitulé du service"
              value={form.title}
              onChange={handleChange('title')}
              required
            />
          </label>

          <label className="register-form__field">
            <span className="register-form__label">Description</span>
            <textarea
              className="register-form__input"
              rows={4}
              placeholder="Détails affichés dans le catalogue et les parcours associés"
              value={form.description}
              onChange={handleChange('description')}
            />
          </label>

          <label className="register-form__field">
            <span className="register-form__label">Mode de facturation</span>
            <select
              className="register-form__input"
              value={form.unit}
              onChange={(event) =>
                setForm((prev) => ({
                  ...prev,
                  unit: event.target.value,
                }))
              }
            >
              {BILLING_MODE_OPTIONS.map((option) => (
                <option key={option.value} value={option.value}>
                  {option.label}
                </option>
              ))}
            </select>
          </label>

          <div className="grid gap-4 md:grid-cols-[1fr_180px]">
            <label className="register-form__field">
              <span className="register-form__label">Durée estimée</span>
              <input
                className="register-form__input"
                type="number"
                min="1"
                step="1"
                inputMode="numeric"
                placeholder="Ex: 2"
                value={form.durationValue}
                onChange={handleChange('durationValue')}
              />
            </label>

            <label className="register-form__field">
              <span className="register-form__label">Unité de durée</span>
              <select
                className="register-form__input"
                value={form.durationUnit}
                onChange={(event) =>
                  setForm((prev) => ({
                    ...prev,
                    durationUnit: event.target.value === 'day' ? 'day' : 'hour',
                  }))
                }
              >
                <option value="hour">Heure(s)</option>
                <option value="day">Jour(s)</option>
              </select>
            </label>
          </div>

          <label className="register-form__field">
            <span className="register-form__label">Prix HT (EUR)</span>
            <input
              className="register-form__input"
              type="number"
              min="0"
              step="0.01"
              value={form.price}
              onChange={handleChange('price')}
            />
          </label>

          <label className="register-form__field">
            <span className="register-form__label">TVA (%)</span>
            <input
              className="register-form__input"
              type="number"
              min="0"
              step="0.1"
              value={form.vatRate}
              onChange={handleChange('vatRate')}
            />
          </label>

          <button type="submit" className="register-form__submit" disabled={saving}>
            {saving ? 'Sauvegarde...' : isEdit ? 'Mettre à jour' : 'Créer'}
          </button>
        </form>
      )}
    </PageContainer>
  );
};
