import { useEffect, useMemo, useState } from 'react';
import { useNavigate, useParams } from 'react-router-dom';

import { useRequireAdmin } from '@/features/admin/hooks/useRequireAdmin';
import {
  createPromotion,
  fetchPromotion,
  fetchPromotionAudiences,
  updatePromotion,
  type PromotionAudienceDefinition,
  type PromotionPayload,
} from '@/features/admin/promotions/api';
import { PageContainer } from '@/shared/components/PageContainer';
import { useToast } from '@/shared/components/ui/toast';
import { useDocumentTitle } from '@/shared/hooks/useDocumentTitle';

type FormState = {
  name: string;
  slug: string;
  description: string;
  discountType: 'percent' | 'fixed_cents';
  discountValue: string;
  audienceKey: string;
  minimumCartTotalEuros: string;
  registeredDays: string;
  minimumOrders: string;
  inactiveDays: string;
  isActive: boolean;
  startsAt: string;
  endsAt: string;
};

const emptyForm: FormState = {
  name: '',
  slug: '',
  description: '',
  discountType: 'percent',
  discountValue: '',
  audienceKey: 'all_users',
  minimumCartTotalEuros: '0',
  registeredDays: '30',
  minimumOrders: '3',
  inactiveDays: '90',
  isActive: true,
  startsAt: '',
  endsAt: '',
};

const centsToEuroInput = (value: number) => (value / 100).toFixed(2);

const euroInputToCents = (value: string) => {
  const normalized = Number.parseFloat(value.replace(',', '.'));
  return Number.isFinite(normalized) ? Math.max(0, Math.round(normalized * 100)) : 0;
};

export const PromotionFormPage = () => {
  const { promotionId } = useParams();
  const isEdit = useMemo(() => Boolean(promotionId), [promotionId]);
  useDocumentTitle(isEdit ? 'Admin - Modifier une promotion' : 'Admin - Nouvelle promotion');
  const navigate = useNavigate();
  const toast = useToast();
  const { isAdmin, loading: guardLoading } = useRequireAdmin();
  const [form, setForm] = useState<FormState>(emptyForm);
  const [audiences, setAudiences] = useState<Record<string, PromotionAudienceDefinition>>({});
  const [loading, setLoading] = useState(false);
  const [initialLoading, setInitialLoading] = useState(false);
  const [error, setError] = useState<string | null>(null);

  useEffect(() => {
    if (!isAdmin) return;
    void fetchPromotionAudiences().then(setAudiences).catch(() => undefined);
  }, [isAdmin]);

  useEffect(() => {
    if (!isAdmin || !isEdit || !promotionId) return;
    setInitialLoading(true);
    void fetchPromotion(Number(promotionId))
      .then((promotion) => {
        setForm({
          name: promotion.name,
          slug: promotion.slug,
          description: promotion.description ?? '',
          discountType: promotion.discountType,
          discountValue: promotion.discountType === 'fixed_cents' ? centsToEuroInput(promotion.discountValue) : String(promotion.discountValue),
          audienceKey: promotion.audienceKey,
          minimumCartTotalEuros: centsToEuroInput(Number(promotion.criteria.minimumCartTotalCents ?? 0)),
          registeredDays: String(promotion.criteria.registeredDays ?? 30),
          minimumOrders: String(promotion.criteria.minimumOrders ?? 3),
          inactiveDays: String(promotion.criteria.inactiveDays ?? 90),
          isActive: promotion.isActive,
          startsAt: promotion.startsAt ? promotion.startsAt.slice(0, 16) : '',
          endsAt: promotion.endsAt ? promotion.endsAt.slice(0, 16) : '',
        });
      })
      .catch((err: any) => {
        const message = err?.message ?? 'Impossible de charger la promotion.';
        setError(message);
        toast.show(message, { variant: 'error' });
      })
      .finally(() => setInitialLoading(false));
  }, [isAdmin, isEdit, promotionId, toast]);

  const payload = useMemo<PromotionPayload>(() => {
    const criteria: Record<string, string | number | boolean> = {
      minimumCartTotalCents: euroInputToCents(form.minimumCartTotalEuros),
    };

    if (form.audienceKey === 'new_users') {
      criteria.registeredDays = Number.parseInt(form.registeredDays, 10) || 30;
    }
    if (form.audienceKey === 'loyal_customers') {
      criteria.minimumOrders = Number.parseInt(form.minimumOrders, 10) || 3;
    }
    if (form.audienceKey === 'inactive_customers') {
      criteria.inactiveDays = Number.parseInt(form.inactiveDays, 10) || 90;
    }

    return {
      name: form.name.trim(),
      slug: form.slug.trim(),
      description: form.description.trim() || null,
      discountType: form.discountType,
      discountValue: form.discountType === 'fixed_cents'
        ? euroInputToCents(form.discountValue)
        : Number.parseInt(form.discountValue, 10) || 0,
      audienceKey: form.audienceKey,
      criteria,
      isActive: form.isActive,
      startsAt: form.startsAt || null,
      endsAt: form.endsAt || null,
    };
  }, [form]);

  const handleSubmit = async (event: React.FormEvent) => {
    event.preventDefault();
    setLoading(true);
    setError(null);

    try {
      if (isEdit && promotionId) {
        await updatePromotion(Number(promotionId), payload);
        toast.show('Promotion mise à jour.', { variant: 'success' });
      } else {
        await createPromotion(payload);
        toast.show('Promotion créée.', { variant: 'success' });
      }
      navigate('/admin/promotions');
    } catch (err: any) {
      const message = err?.message ?? 'Enregistrement impossible.';
      setError(message);
      toast.show(message, { variant: 'error' });
    } finally {
      setLoading(false);
    }
  };

  if (guardLoading) {
    return <PageContainer title="Promotion"><p className="muted">Vérification des droits...</p></PageContainer>;
  }
  if (!isAdmin) {
    return <PageContainer title="Promotion"><div className="register-form__alert">Accès restreint aux administrateurs.</div></PageContainer>;
  }

  return (
    <PageContainer
      title={isEdit ? 'Modifier une promotion' : 'Nouvelle promotion'}
      headerActions={
        <button
          type="button"
          className="register-form__submit"
          style={{ background: '#e5e7eb', color: '#111827' }}
          onClick={() => navigate('/admin/promotions')}
        >
          Retour à la liste
        </button>
      }
    >
      {error && <div className="register-form__alert">{error}</div>}

      {initialLoading ? (
        <p className="muted">Chargement...</p>
      ) : (
        <form onSubmit={handleSubmit} className="register-form-card" style={{ display: 'grid', gap: 16 }}>
          <label className="register-form__field">
            <span className="register-form__label">Nom</span>
            <input className="register-form__input" value={form.name} onChange={(event) => setForm((prev) => ({ ...prev, name: event.target.value }))} />
          </label>

          <label className="register-form__field">
            <span className="register-form__label">Slug</span>
            <input className="register-form__input" value={form.slug} onChange={(event) => setForm((prev) => ({ ...prev, slug: event.target.value }))} />
          </label>

          <label className="register-form__field">
            <span className="register-form__label">Description</span>
            <input className="register-form__input" value={form.description} onChange={(event) => setForm((prev) => ({ ...prev, description: event.target.value }))} />
          </label>

          <div className="grid gap-4 md:grid-cols-2">
            <label className="register-form__field">
              <span className="register-form__label">Type de remise</span>
              <select className="register-form__input" value={form.discountType} onChange={(event) => setForm((prev) => ({ ...prev, discountType: event.target.value as 'percent' | 'fixed_cents' }))}>
                <option value="percent">Pourcentage</option>
                <option value="fixed_cents">Montant fixe en euros</option>
              </select>
            </label>
            <label className="register-form__field">
              <span className="register-form__label">Valeur {form.discountType === 'percent' ? '(%)' : '(EUR)'}</span>
              <input className="register-form__input" type="number" min={1} step={form.discountType === 'percent' ? 1 : 0.01} value={form.discountValue} onChange={(event) => setForm((prev) => ({ ...prev, discountValue: event.target.value }))} placeholder={form.discountType === 'percent' ? 'Ex: 10' : 'Ex: 15,00'} />
            </label>
          </div>

          <label className="register-form__field">
            <span className="register-form__label">Audience</span>
            <select className="register-form__input" value={form.audienceKey} onChange={(event) => setForm((prev) => ({ ...prev, audienceKey: event.target.value }))}>
              {Object.entries(audiences).map(([key, audience]) => (
                <option key={key} value={key}>
                  {audience.label}
                </option>
              ))}
            </select>
          </label>

          {audiences[form.audienceKey] ? (
            <div className="rounded-2xl border border-slate-200 bg-slate-50 px-4 py-4 text-sm text-slate-700">
              <strong className="block text-slate-900">{audiences[form.audienceKey].label}</strong>
              <span>{audiences[form.audienceKey].description}</span>
            </div>
          ) : null}

          <label className="register-form__field">
            <span className="register-form__label">Panier minimum en euros</span>
            <input className="register-form__input" type="number" min={0} step={0.01} value={form.minimumCartTotalEuros} onChange={(event) => setForm((prev) => ({ ...prev, minimumCartTotalEuros: event.target.value }))} />
          </label>

          {form.audienceKey === 'new_users' && (
            <label className="register-form__field">
              <span className="register-form__label">Inscription depuis moins de X jours</span>
              <input className="register-form__input" type="number" min={1} value={form.registeredDays} onChange={(event) => setForm((prev) => ({ ...prev, registeredDays: event.target.value }))} />
            </label>
          )}

          {form.audienceKey === 'loyal_customers' && (
            <label className="register-form__field">
              <span className="register-form__label">Nombre minimum de commandes</span>
              <input className="register-form__input" type="number" min={2} value={form.minimumOrders} onChange={(event) => setForm((prev) => ({ ...prev, minimumOrders: event.target.value }))} />
            </label>
          )}

          {form.audienceKey === 'inactive_customers' && (
            <label className="register-form__field">
              <span className="register-form__label">Inactivité depuis X jours</span>
              <input className="register-form__input" type="number" min={30} value={form.inactiveDays} onChange={(event) => setForm((prev) => ({ ...prev, inactiveDays: event.target.value }))} />
            </label>
          )}

          <div className="grid gap-4 md:grid-cols-2">
            <label className="register-form__field">
              <span className="register-form__label">Début</span>
              <input className="register-form__input" type="datetime-local" value={form.startsAt} onChange={(event) => setForm((prev) => ({ ...prev, startsAt: event.target.value }))} />
            </label>
            <label className="register-form__field">
              <span className="register-form__label">Fin</span>
              <input className="register-form__input" type="datetime-local" value={form.endsAt} onChange={(event) => setForm((prev) => ({ ...prev, endsAt: event.target.value }))} />
            </label>
          </div>

          <label style={{ display: 'flex', alignItems: 'center', gap: 8 }}>
            <input type="checkbox" checked={form.isActive} onChange={(event) => setForm((prev) => ({ ...prev, isActive: event.target.checked }))} />
            <span>Promotion active</span>
          </label>

          <button className="register-form__submit" type="submit" disabled={loading}>
            {loading ? 'Enregistrement...' : isEdit ? 'Mettre à jour' : 'Créer'}
          </button>
        </form>
      )}
    </PageContainer>
  );
};
