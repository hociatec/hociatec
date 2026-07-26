import { useEffect, useMemo, useState, type FormEvent } from 'react';
import { useSearchParams } from 'react-router-dom';

import {
  fetchMarketingSegments,
  fetchMarketingTemplates,
  previewMarketingAudience,
  sendMarketingCampaign,
  type MarketingAudiencePreview,
  type MarketingSegmentDefinition,
  type MarketingTemplate,
} from '@/features/admin/marketing/api';
import { buildCampaignCriteria } from '@/features/admin/marketing/lib/buildCampaignCriteria';
import { segmentAdvice } from '@/features/admin/marketing/lib/segmentAdvice';
import {
  emptyCampaignForm,
  type CampaignFormState,
} from '@/features/admin/marketing/types/campaignForm';
import { getHttpErrorMessage } from '@/shared/lib/httpClient';

export const useMarketingCampaignForm = () => {
  const [searchParams, setSearchParams] = useSearchParams();
  const [templates, setTemplates] = useState<MarketingTemplate[]>([]);
  const [segments, setSegments] = useState<Record<string, MarketingSegmentDefinition>>({});
  const [preview, setPreview] = useState<MarketingAudiencePreview | null>(null);
  const [form, setForm] = useState<CampaignFormState>(emptyCampaignForm);
  const [loading, setLoading] = useState(true);
  const [saving, setSaving] = useState(false);
  const [previewLoading, setPreviewLoading] = useState(false);
  const [error, setError] = useState<string | null>(null);
  const [message, setMessage] = useState<string | null>(null);

  useEffect(() => {
    let cancelled = false;
    setLoading(true);
    setError(null);
    void Promise.all([fetchMarketingTemplates(), fetchMarketingSegments('campaigns')])
      .then(([templateItems, segmentItems]) => {
        if (cancelled) return;
        setTemplates(templateItems.filter((item) => item.scenarioKey in segmentItems));
        setSegments(segmentItems);
      })
      .catch((reason) => {
        if (!cancelled)
          setError(getHttpErrorMessage(reason, 'Impossible de charger le module marketing.'));
      })
      .finally(() => {
        if (!cancelled) setLoading(false);
      });
    return () => {
      cancelled = true;
    };
  }, []);

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

  const criteria = useMemo(() => buildCampaignCriteria(form), [form]);
  const activeTemplates = useMemo(() => templates.filter((item) => item.isActive), [templates]);
  const templatesForSegment = useMemo(
    () => activeTemplates.filter((item) => item.scenarioKey === form.segmentKey),
    [activeTemplates, form.segmentKey],
  );
  const audienceAdvice = segmentAdvice[form.segmentKey] ?? segmentAdvice.all_verified_users;

  const handlePreview = async () => {
    setPreviewLoading(true);
    setError(null);
    try {
      const result = await previewMarketingAudience(form.segmentKey, criteria);
      setPreview(result.preview);
      setSegments(result.segments);
    } catch (reason) {
      setError(getHttpErrorMessage(reason, 'Prévisualisation impossible.'));
    } finally {
      setPreviewLoading(false);
    }
  };

  const handleSend = async (event: FormEvent) => {
    event.preventDefault();
    if (!form.name.trim() || !form.subject.trim() || !form.htmlBody.trim()) {
      setError('Veuillez renseigner le nom, l’objet et le contenu HTML.');
      return;
    }
    setSaving(true);
    setError(null);
    setMessage(null);
    try {
      const sent = await sendMarketingCampaign({
        name: form.name.trim(),
        templateId: form.templateId ? Number(form.templateId) : null,
        segmentKey: form.segmentKey,
        criteria,
        subject: form.subject,
        htmlBody: form.htmlBody,
        textBody: form.textBody || null,
      });
      setMessage(`Campagne envoyée à ${sent.recipientsCount} destinataire(s).`);
    } catch (reason) {
      setError(getHttpErrorMessage(reason, 'Envoi impossible.'));
    } finally {
      setSaving(false);
    }
  };

  return {
    templates,
    segments,
    preview,
    form,
    setForm,
    loading,
    saving,
    previewLoading,
    error,
    message,
    activeTemplates,
    templatesForSegment,
    audienceAdvice,
    handlePreview,
    handleSend,
  };
};
