import { getHttpErrorMessage } from '@/shared/lib/httpClient';
import { useEffect, useMemo, useState, type FormEvent } from 'react';
import { useNavigate, useParams } from 'react-router';

import { createPromotion, fetchPromotion, fetchPromotionAudiences, updatePromotion, type PromotionAudienceDefinition, type PromotionPayload } from '@/features/admin/promotions/api';
import { PromotionFormFields } from '@/features/admin/promotions/components/PromotionFormFields';
import { PageContainer } from '@/shared/components/layout/PageContainer';
import { FeedbackMessage, LoadingState } from '@/shared/components/ui/page-state';
import { useToast } from '@/shared/components/ui/toast';
import { useDocumentTitle } from '@/shared/hooks/useDocumentTitle';

export type FormState = { name: string; slug: string; description: string; discountType: 'percent' | 'fixed_cents'; discountValue: string; audienceKey: string; minimumCartTotalEuros: string; registeredDays: string; minimumOrders: string; inactiveDays: string; isActive: boolean; startsAt: string; endsAt: string };
const emptyForm: FormState = { name: '', slug: '', description: '', discountType: 'percent', discountValue: '', audienceKey: 'all_users', minimumCartTotalEuros: '0', registeredDays: '30', minimumOrders: '3', inactiveDays: '90', isActive: true, startsAt: '', endsAt: '' };
const centsToEuroInput = (value: number) => (value / 100).toFixed(2);
const euroInputToCents = (value: string) => { const normalized = Number.parseFloat(value.replace(',', '.')); return Number.isFinite(normalized) ? Math.max(0, Math.round(normalized * 100)) : 0; };

export const PromotionFormPage = () => {
  const { promotionId } = useParams();
  const isEdit = Boolean(promotionId);
  useDocumentTitle(isEdit ? 'Admin - Modifier une promotion' : 'Admin - Nouvelle promotion');
  const navigate = useNavigate();
  const toast = useToast();
  const [form, setForm] = useState<FormState>(emptyForm);
  const [audiences, setAudiences] = useState<Record<string, PromotionAudienceDefinition>>({});
  const [loading, setLoading] = useState(false);
  const [initialLoading, setInitialLoading] = useState(false);
  const [error, setError] = useState<string | null>(null);

  useEffect(() => { void fetchPromotionAudiences().then(setAudiences).catch(() => undefined); }, []);
  useEffect(() => {
    if (!isEdit || !promotionId) return;
    setInitialLoading(true);
    void fetchPromotion(Number(promotionId)).then((promotion) => setForm({ name: promotion.name, slug: promotion.slug, description: promotion.description ?? '', discountType: promotion.discountType, discountValue: promotion.discountType === 'fixed_cents' ? centsToEuroInput(promotion.discountValue) : String(promotion.discountValue), audienceKey: promotion.audienceKey, minimumCartTotalEuros: centsToEuroInput(Number(promotion.criteria.minimumCartTotalCents ?? 0)), registeredDays: String(promotion.criteria.registeredDays ?? 30), minimumOrders: String(promotion.criteria.minimumOrders ?? 3), inactiveDays: String(promotion.criteria.inactiveDays ?? 90), isActive: promotion.isActive, startsAt: promotion.startsAt ? promotion.startsAt.slice(0, 16) : '', endsAt: promotion.endsAt ? promotion.endsAt.slice(0, 16) : '' })).catch((err) => { const message = getHttpErrorMessage(err, 'Impossible de charger la promotion.'); setError(message); toast.show(message, { variant: 'error' }); }).finally(() => setInitialLoading(false));
  }, [isEdit, promotionId, toast]);

  const payload = useMemo<PromotionPayload>(() => {
    const criteria: Record<string, string | number | boolean> = { minimumCartTotalCents: euroInputToCents(form.minimumCartTotalEuros) };
    if (form.audienceKey === 'new_users') criteria.registeredDays = Number.parseInt(form.registeredDays, 10) || 30;
    if (form.audienceKey === 'loyal_customers') criteria.minimumOrders = Number.parseInt(form.minimumOrders, 10) || 3;
    if (form.audienceKey === 'inactive_customers') criteria.inactiveDays = Number.parseInt(form.inactiveDays, 10) || 90;
    return { name: form.name.trim(), slug: form.slug.trim(), description: form.description.trim() || null, discountType: form.discountType, discountValue: form.discountType === 'fixed_cents' ? euroInputToCents(form.discountValue) : Number.parseInt(form.discountValue, 10) || 0, audienceKey: form.audienceKey, criteria, isActive: form.isActive, startsAt: form.startsAt || null, endsAt: form.endsAt || null };
  }, [form]);

  const handleSubmit = async (event: FormEvent) => {
    event.preventDefault(); setLoading(true); setError(null);
    try { const response = isEdit && promotionId ? await updatePromotion(Number(promotionId), payload) : await createPromotion(payload); toast.show(response.message ?? (isEdit ? 'La promotion a bien été mise à jour.' : 'La promotion a bien été créée.'), { variant: 'success' }); navigate('/admin/promotions'); }
    catch (err) { const message = getHttpErrorMessage(err, 'Enregistrement impossible.'); setError(message); toast.show(message, { variant: 'error' }); }
    finally { setLoading(false); }
  };

  return <PageContainer size="admin" title={isEdit ? 'Modifier une promotion' : 'Nouvelle promotion'} headerActions={<button type="button" className="catalog-admin-actions__edit" onClick={() => navigate('/admin/promotions')}>Retour à la liste</button>}>
    {error && <FeedbackMessage>{error}</FeedbackMessage>}
    {initialLoading ? <LoadingState>Chargement...</LoadingState> : <form onSubmit={handleSubmit} className="register-form-card form-card-grid"><PromotionFormFields form={form} setForm={setForm} audiences={audiences} /><button className="register-form__submit" type="submit" disabled={loading}>{loading ? 'Enregistrement...' : isEdit ? 'Mettre à jour' : 'Créer'}</button></form>}
  </PageContainer>;
};
