import { getHttpErrorMessage } from '@/shared/lib/httpClient';
import { useEffect, useMemo, useState, type FormEvent } from 'react';
import { useNavigate, useParams } from 'react-router';
import { useMutation, useQuery, useQueryClient } from '@tanstack/react-query';

import { createPromotion, fetchPromotion, fetchPromotionAudiences, updatePromotion, type PromotionAudienceDefinition, type PromotionPayload } from '@/features/admin/promotions/api';
import { PromotionFormFields } from '@/features/admin/promotions/components/PromotionFormFields';
import { PageContainer } from '@/shared/components/layout/PageContainer';
import { FeedbackMessage, LoadingState } from '@/shared/components/ui/page-state';
import { useToast } from '@/shared/components/ui/toast';
import { useDocumentTitle } from '@/shared/hooks/useDocumentTitle';
import {
  formatApiDateForDateTimeInput,
  formatEuroInputFromCents,
  parseEuroInputToCents,
} from '@/shared/lib/formatters';
import type { PromotionFormState } from '@/features/admin/promotions/types/promotionFormTypes';
import { adminPromotionQueryKeys } from '@/shared/lib/queryKeys';

const emptyForm: PromotionFormState = { name: '', slug: '', description: '', discountType: 'percent', discountValue: '', audienceKey: 'all_users', minimumCartTotalEuros: '0', registeredDays: '30', minimumOrders: '3', inactiveDays: '90', isActive: true, startsAt: '', endsAt: '' };

export const PromotionFormPage = () => {
  const { promotionId } = useParams();
  const isEdit = Boolean(promotionId);
  useDocumentTitle(isEdit ? 'Admin - Modifier une promotion' : 'Admin - Nouvelle promotion');
  const navigate = useNavigate();
  const toast = useToast();
  const queryClient = useQueryClient();
  const [form, setForm] = useState<PromotionFormState>(emptyForm);
  const [error, setError] = useState<string | null>(null);
  const audiencesQuery = useQuery<Record<string, PromotionAudienceDefinition>, Error>({
    queryKey: adminPromotionQueryKeys.audiences(),
    queryFn: fetchPromotionAudiences,
  });
  const promotionQuery = useQuery({
    queryKey: adminPromotionQueryKeys.detail(promotionId ? Number(promotionId) : null),
    queryFn: () => fetchPromotion(Number(promotionId)),
    enabled: isEdit && Boolean(promotionId),
  });
  const audiences = audiencesQuery.data ?? {};

  useEffect(() => {
    if (!promotionQuery.data) return;
    const promotion = promotionQuery.data;
    setForm({ name: promotion.name, slug: promotion.slug, description: promotion.description ?? '', discountType: promotion.discountType, discountValue: promotion.discountType === 'fixed_cents' ? formatEuroInputFromCents(promotion.discountValue) : String(promotion.discountValue), audienceKey: promotion.audienceKey, minimumCartTotalEuros: formatEuroInputFromCents(Number(promotion.criteria.minimumCartTotalCents ?? 0)), registeredDays: String(promotion.criteria.registeredDays ?? 30), minimumOrders: String(promotion.criteria.minimumOrders ?? 3), inactiveDays: String(promotion.criteria.inactiveDays ?? 90), isActive: promotion.isActive, startsAt: formatApiDateForDateTimeInput(promotion.startsAt), endsAt: formatApiDateForDateTimeInput(promotion.endsAt) });
  }, [promotionQuery.data]);

  const payload = useMemo<PromotionPayload>(() => {
    const criteria: Record<string, string | number | boolean> = { minimumCartTotalCents: parseEuroInputToCents(form.minimumCartTotalEuros) };
    if (form.audienceKey === 'new_users') criteria.registeredDays = Number.parseInt(form.registeredDays, 10) || 30;
    if (form.audienceKey === 'loyal_customers') criteria.minimumOrders = Number.parseInt(form.minimumOrders, 10) || 3;
    if (form.audienceKey === 'inactive_customers') criteria.inactiveDays = Number.parseInt(form.inactiveDays, 10) || 90;
    return { name: form.name.trim(), slug: form.slug.trim(), description: form.description.trim() || null, discountType: form.discountType, discountValue: form.discountType === 'fixed_cents' ? parseEuroInputToCents(form.discountValue) : Number.parseInt(form.discountValue, 10) || 0, audienceKey: form.audienceKey, criteria, isActive: form.isActive, startsAt: form.startsAt || null, endsAt: form.endsAt || null };
  }, [form]);

  const saveMutation = useMutation({
    mutationFn: () =>
      isEdit && promotionId
        ? updatePromotion(Number(promotionId), payload)
        : createPromotion(payload),
    onSuccess: (response) => {
      void queryClient.invalidateQueries({ queryKey: adminPromotionQueryKeys.overview() });
      toast.show(response.message ?? (isEdit ? 'La promotion a bien été mise à jour.' : 'La promotion a bien été créée.'), { variant: 'success' });
      navigate('/admin/promotions');
    },
    onError: (err) => {
      const message = getHttpErrorMessage(err, 'Enregistrement impossible.');
      setError(message);
      toast.show(message, { variant: 'error' });
    },
  });

  const handleSubmit = async (event: FormEvent) => {
    event.preventDefault(); setError(null); saveMutation.mutate();
  };

  return <PageContainer size="admin" title={isEdit ? 'Modifier une promotion' : 'Nouvelle promotion'} headerActions={<button type="button" className="catalog-admin-actions__edit" onClick={() => navigate('/admin/promotions')}>Retour à la liste</button>}>
    {(error || promotionQuery.error) && <FeedbackMessage>{error ?? getHttpErrorMessage(promotionQuery.error, 'Impossible de charger la promotion.')}</FeedbackMessage>}
    {promotionQuery.isLoading ? <LoadingState>Chargement...</LoadingState> : <form onSubmit={handleSubmit} className="register-form-card form-card-grid"><PromotionFormFields form={form} setForm={setForm} audiences={audiences} /><button className="register-form__submit" type="submit" disabled={saveMutation.isPending}>{saveMutation.isPending ? 'Enregistrement...' : isEdit ? 'Mettre à jour' : 'Créer'}</button></form>}
  </PageContainer>;
};
