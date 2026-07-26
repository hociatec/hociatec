import { getHttpErrorMessage } from '@/shared/lib/httpClient';
import { useEffect, useState } from 'react';
import { useNavigate, useParams } from 'react-router-dom';

import {
  createAdminQuoteService,
  fetchAdminQuoteService,
  updateAdminQuoteService,
} from '@/features/quotes/api/quotesApi';
import { PageContainer } from '@/shared/components/PageContainer';
import { FeedbackMessage, LoadingState } from '@/shared/components/ui/page-state';
import { useDocumentTitle } from '@/shared/hooks/useDocumentTitle';
import {
  BILLING_MODE_OPTIONS,
  ServiceFormFields,
  type ServiceFormState,
} from '@/features/admin/quotes/components/ServiceFormFields';

type ServicePayload = {
  title: string;
  description: string;
  unit: string;
  durationValue: number | '';
  durationUnit: 'hour' | 'day' | '';
  price: number;
  vatRate: number;
};

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
    const parsedDurationUnit: ServicePayload['durationUnit'] =
      durationValue === '' ? '' : form.durationUnit;

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
        const response = await updateAdminQuoteService(serviceId, payload);
        setMessage(response.message ?? 'Le service a bien été mis à jour.');
      } else {
        const response = await createAdminQuoteService(payload);
        setMessage(response.message ?? 'Le service a bien été créé.');
      }
      navigate('/admin/services');
    } catch (e) {
      setError(getHttpErrorMessage(e, 'Échec de sauvegarde.'));
    } finally {
      setSaving(false);
    }
  };

  return (
    <PageContainer
      size="admin"
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
        Renseignez ici les informations complètes du service, y compris sa durée estimée lorsqu’elle
        est connue.
      </p>
      {error && <FeedbackMessage>{error}</FeedbackMessage>}
      {message && <FeedbackMessage variant="success">{message}</FeedbackMessage>}

      {initialLoading && isEdit ? (
        <LoadingState>Chargement du service...</LoadingState>
      ) : (
        <form onSubmit={handleSubmit} className="register-form-card form-card-grid">
          <ServiceFormFields form={form} setForm={setForm} />

          <button type="submit" className="register-form__submit" disabled={saving}>
            {saving ? 'Sauvegarde...' : isEdit ? 'Mettre à jour' : 'Créer'}
          </button>
        </form>
      )}
    </PageContainer>
  );
};
