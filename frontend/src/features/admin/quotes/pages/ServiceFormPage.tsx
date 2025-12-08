import { type ChangeEvent, useEffect, useState } from 'react';
import { useNavigate, useParams } from 'react-router-dom';

import { createAdminQuoteService, fetchAdminQuoteService, updateAdminQuoteService } from '@/features/quotes/api';
import { PageContainer } from '@/shared/components/PageContainer';
import { useRequireAdmin } from '@/features/admin/hooks/useRequireAdmin';
import { useDocumentTitle } from '@/shared/hooks/useDocumentTitle';

type ServiceFormState = {
  title: string;
  description: string;
  unit: string;
  price: string;
  vatRate: string;
};

const emptyForm: ServiceFormState = {
  title: '',
  description: '',
  unit: '',
  price: '0',
  vatRate: '20',
};

export const ServiceFormPage = () => {
  const params = useParams<{ serviceId?: string }>();
  const parsedServiceId = params.serviceId ? Number.parseInt(params.serviceId, 10) : NaN;
  const serviceId = Number.isNaN(parsedServiceId) ? null : parsedServiceId;
  const isEdit = serviceId !== null;
  useDocumentTitle(isEdit ? 'Admin - Modifier un service' : 'Admin - Nouveau service');
  const { isAdmin, loading: guardLoading } = useRequireAdmin();
  const navigate = useNavigate();

  const [form, setForm] = useState<ServiceFormState>(emptyForm);
  const [initialLoading, setInitialLoading] = useState(false);
  const [saving, setSaving] = useState(false);
  const [error, setError] = useState<string | null>(null);
  const [message, setMessage] = useState<string | null>(null);

  useEffect(() => {
    if (!isAdmin || !isEdit || serviceId === null) {
      return;
    }
    setInitialLoading(true);
    setError(null);
    void fetchAdminQuoteService(serviceId)
      .then((svc) => {
        setForm({
          title: svc?.title ?? '',
          description: svc?.description ?? '',
          unit: svc?.unit ?? '',
          price: svc ? (svc.priceCents / 100).toFixed(2) : '0',
          vatRate: svc ? String(svc.vatRate ?? 0) : '0',
        });
      })
      .catch((e: any) => setError(e?.message ?? 'Chargement impossible.'))
      .finally(() => setInitialLoading(false));
  }, [isAdmin, isEdit, serviceId]);

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

  const buildPayload = () => {
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
    const unit = form.unit.trim();

    return {
      title,
      description,
      unit,
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
      navigate('/admin/quotes/services');
    } catch (e: any) {
      const serverMessage = e?.response?.data?.message ?? e?.message;
      setError(serverMessage ?? 'Echec de sauvegarde.');
    } finally {
      setSaving(false);
    }
  };

  if (guardLoading) {
    return (
      <PageContainer title={isEdit ? 'Modifier un service' : 'Nouveau service'}>
        <p className="muted">Verification des droits...</p>
      </PageContainer>
    );
  }
  if (!isAdmin) {
    return (
      <PageContainer title={isEdit ? 'Modifier un service' : 'Nouveau service'}>
        <div className="register-form__alert">Acces restreint aux administrateurs.</div>
      </PageContainer>
    );
  }

  return (
    <PageContainer
      title={isEdit ? 'Modifier un service' : 'Nouveau service'}
      headerActions={
        <button
          type="button"
          className="register-form__submit"
          style={{ background: '#e5e7eb', color: '#111827' }}
          onClick={() => navigate('/admin/quotes/services')}
        >
          Retour a la liste
        </button>
      }
    >
      {error && <div className="register-form__alert">{error}</div>}
      {message && (
        <div className="register-form__alert" style={{ background: '#ecfdf5', color: '#047857' }}>
          {message}
        </div>
      )}

      {initialLoading && isEdit ? (
        <p className="muted">Chargement du service...</p>
      ) : (
        <form
          onSubmit={handleSubmit}
          className="register-form-card"
          style={{ display: 'grid', gap: 16 }}
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
              placeholder="Details affiches sur le devis"
              value={form.description}
              onChange={handleChange('description')}
            />
          </label>

          <label className="register-form__field">
            <span className="register-form__label">Unite (ex: heure)</span>
            <input
              className="register-form__input"
              value={form.unit}
              onChange={handleChange('unit')}
            />
          </label>

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
            {saving ? 'Sauvegarde...' : isEdit ? 'Mettre a jour' : 'Creer'}
          </button>
        </form>
      )}
    </PageContainer>
  );
};
