import { useEffect, useMemo, useState, type FormEvent } from 'react';
import { useSearchParams } from 'react-router';
import { useMutation, useQuery, useQueryClient } from '@tanstack/react-query';

import {
  fetchMarketingSegments,
  fetchMarketingTemplates,
  previewMarketingAudience,
  sendMarketingCampaign,
  type MarketingAudiencePreview,
  type MarketingSegmentDefinition,
} from '@/features/admin/marketing/api';
import { buildCampaignCriteria } from '@/features/admin/marketing/lib/buildCampaignCriteria';
import { segmentAdvice } from '@/features/admin/marketing/lib/segmentAdvice';
import {
  emptyCampaignForm,
  type CampaignFormState,
} from '@/features/admin/marketing/types/campaignForm';
import { getHttpErrorMessage } from '@/shared/lib/httpClient';
import { adminMarketingQueryKeys } from '@/features/admin/marketing/queryKeys';

export const useMarketingCampaignForm = () => {
  const [searchParams, setSearchParams] = useSearchParams();
  const [preview, setPreview] = useState<MarketingAudiencePreview | null>(null);
  const [form, setForm] = useState<CampaignFormState>(emptyCampaignForm);
  const queryClient = useQueryClient();
  const [error, setError] = useState<string | null>(null);
  const [message, setMessage] = useState<string | null>(null);
  const optionsQuery = useQuery({
    queryKey: adminMarketingQueryKeys.campaignFormOptions(),
    queryFn: async () => {
      const [templateItems, segments] = await Promise.all([
        fetchMarketingTemplates(),
        fetchMarketingSegments('campaigns'),
      ]);
      return {
        templates: templateItems.filter((item) => item.scenarioKey in segments),
        segments,
      };
    },
  });
  const templates = optionsQuery.data?.templates ?? [];
  const segments: Record<string, MarketingSegmentDefinition> = optionsQuery.data?.segments ?? {};
  const previewMutation = useMutation({
    mutationFn: () => previewMarketingAudience(form.segmentKey, criteria),
    onSuccess: (result) => {
      setPreview(result.preview);
      queryClient.setQueryData(adminMarketingQueryKeys.segments('campaigns'), result.segments);
    },
    onError: (reason) => setError(getHttpErrorMessage(reason, 'Prévisualisation impossible.')),
  });
  const sendMutation = useMutation({
    mutationFn: () =>
      sendMarketingCampaign({
        name: form.name.trim(),
        templateId: form.templateId ? Number(form.templateId) : null,
        segmentKey: form.segmentKey,
        criteria,
        subject: form.subject,
        htmlBody: form.htmlBody,
        textBody: form.textBody || null,
      }),
    onSuccess: (sent) => {
      void queryClient.invalidateQueries({ queryKey: adminMarketingQueryKeys.campaigns() });
      void queryClient.invalidateQueries({ queryKey: adminMarketingQueryKeys.overview() });
      setMessage(`Campagne envoyée à ${sent.recipientsCount} destinataire(s).`);
    },
    onError: (reason) => setError(getHttpErrorMessage(reason, 'Envoi impossible.')),
  });

  useEffect(() => {
    const templateId = searchParams.get('templateId');
    if (!templateId || templates.length === 0) return;
    const template = templates.find((item) => String(item.id) === templateId);
    if (!template) return;
    setForm((current) => ({ ...current, templateId }));
    setSearchParams(
      (current) => {
        const next = new URLSearchParams(current);
        next.delete('templateId');
        return next;
      },
      { replace: true },
    );
  }, [searchParams, setSearchParams, templates]);

  const selectedTemplate = useMemo(
    () => templates.find((item) => String(item.id) === form.templateId) ?? null,
    [form.templateId, templates],
  );

  useEffect(() => {
    if (!selectedTemplate) return;
    setForm((current) => ({
      ...current,
      subject: selectedTemplate.subjectTemplate,
      htmlBody: selectedTemplate.htmlBody,
      textBody: selectedTemplate.textBody ?? '',
      segmentKey: selectedTemplate.scenarioKey,
      name: current.name || `Campagne - ${selectedTemplate.name}`,
    }));
  }, [selectedTemplate]);

  useEffect(() => {
    const defaults = segments[form.segmentKey]?.defaults;
    if (!defaults) return;
    setForm((current) => ({
      ...current,
      minimumOrders:
        defaults.minimumOrders !== undefined
          ? String(defaults.minimumOrders)
          : current.minimumOrders,
      inactiveDays:
        defaults.inactiveDays !== undefined ? String(defaults.inactiveDays) : current.inactiveDays,
      registeredDays:
        defaults.registeredDays !== undefined
          ? String(defaults.registeredDays)
          : current.registeredDays,
      recentDays:
        defaults.recentDays !== undefined ? String(defaults.recentDays) : current.recentDays,
      minimumTotalCents:
        defaults.minimumTotalCents !== undefined
          ? String(defaults.minimumTotalCents)
          : current.minimumTotalCents,
      minimumPendingReviews:
        defaults.minimumPendingReviews !== undefined
          ? String(defaults.minimumPendingReviews)
          : current.minimumPendingReviews,
    }));
  }, [form.segmentKey, segments]);

  const criteria = buildCampaignCriteria(form);
  const activeTemplates = useMemo(() => templates.filter((item) => item.isActive), [templates]);
  const templatesForSegment = useMemo(
    () => activeTemplates.filter((item) => item.scenarioKey === form.segmentKey),
    [activeTemplates, form.segmentKey],
  );
  const audienceAdvice = segmentAdvice[form.segmentKey] ?? segmentAdvice.all_verified_users;

  const handlePreview = async () => {
    setError(null);
    previewMutation.mutate();
  };

  const handleSend = async (event: FormEvent) => {
    event.preventDefault();
    if (!form.name.trim() || !form.subject.trim() || !form.htmlBody.trim()) {
      setError('Veuillez renseigner le nom, l’objet et le contenu HTML.');
      return;
    }
    setError(null);
    setMessage(null);
    sendMutation.mutate();
  };

  return {
    templates,
    segments,
    preview,
    form,
    setForm,
    loading: optionsQuery.isLoading,
    saving: sendMutation.isPending,
    previewLoading: previewMutation.isPending,
    error:
      error ??
      (optionsQuery.error
        ? getHttpErrorMessage(optionsQuery.error, 'Impossible de charger le module marketing.')
        : null),
    message,
    activeTemplates,
    templatesForSegment,
    audienceAdvice,
    handlePreview,
    handleSend,
  };
};
