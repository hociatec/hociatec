import { useEffect, useState } from 'react';
import { useLocation, useNavigate, useParams } from 'react-router';
import { useMutation, useQuery, useQueryClient } from '@tanstack/react-query';
import { useDelayedNavigation } from '@/shared/hooks/useDelayedNavigation';
import {
  createMarketingTemplate,
  fetchMarketingSegments,
  fetchMarketingTemplate,
  updateMarketingTemplate,
  type MarketingTemplatePayload,
} from '../api';
import { getHttpErrorMessage } from '@/shared/lib/httpClient';
import { adminMarketingQueryKeys } from '@/features/admin/marketing/queryKeys';
import { parseNullablePositiveInteger } from '@/shared/lib/parsers';

export const defaultMarketingTemplateForm: MarketingTemplatePayload = {
  name: '',
  slug: '',
  scenarioKey: 'order_created',
  subjectTemplate: 'Commande {{order_number}} enregistrée',
  htmlBody: '<p>Bonjour {{first_name}},</p><p>Votre message ici.</p>',
  textBody: 'Bonjour {{first_name}},\n\nVotre message ici.',
  isActive: true,
};
export const useMarketingTemplateForm = () => {
  const { templateId } = useParams();
  const location = useLocation();
  const navigate = useNavigate();
  const navigateWithDelay = useDelayedNavigation(500);
  const queryClient = useQueryClient();
  const parsedTemplateId = parseNullablePositiveInteger(templateId);
  const isEdit = parsedTemplateId !== null;
  const isTransactionalView = location.pathname.startsWith('/admin/transactional-emails');
  const [form, setForm] = useState<MarketingTemplatePayload>(defaultMarketingTemplateForm);
  const [error, setError] = useState<string | null>(null);
  const [message, setMessage] = useState<string | null>(null);
  const segmentType = isTransactionalView ? 'transactional' : 'templates';
  const segmentsQuery = useQuery({
    queryKey: adminMarketingQueryKeys.segments(segmentType),
    queryFn: () => fetchMarketingSegments(segmentType),
  });
  const templateQuery = useQuery({
    queryKey: adminMarketingQueryKeys.template(isEdit ? parsedTemplateId : null),
    queryFn: () => fetchMarketingTemplate(parsedTemplateId || 0),
    enabled: isEdit,
  });
  const saveMutation = useMutation({
    mutationFn: (payload: MarketingTemplatePayload) =>
      isEdit
        ? updateMarketingTemplate(parsedTemplateId, payload)
        : createMarketingTemplate(payload),
    onSuccess: (response) => {
      void queryClient.invalidateQueries({ queryKey: adminMarketingQueryKeys.templates() });
      setMessage(
        response.message ??
          (isEdit
            ? 'Le modèle d’e-mail a bien été mis à jour.'
            : 'Le modèle d’e-mail a bien été créé.'),
      );
      navigateWithDelay(
        isTransactionalView ? '/admin/transactional-emails' : '/admin/marketing/templates',
      );
    },
    onError: (e) => setError(getHttpErrorMessage(e, 'Enregistrement impossible.')),
  });

  useEffect(() => {
    if (!templateQuery.data) return;
    setForm({
      name: templateQuery.data.name,
      slug: templateQuery.data.slug,
      scenarioKey: templateQuery.data.scenarioKey,
      subjectTemplate: templateQuery.data.subjectTemplate,
      htmlBody: templateQuery.data.htmlBody,
      textBody: templateQuery.data.textBody ?? '',
      isActive: templateQuery.data.isActive,
    });
  }, [templateQuery.data]);
  const handleSubmit = async (event: React.FormEvent) => {
    event.preventDefault();
    if (
      !form.name.trim() ||
      !form.slug.trim() ||
      !form.subjectTemplate.trim() ||
      !form.htmlBody.trim()
    ) {
      setError('Veuillez renseigner tous les champs obligatoires.');
      return;
    }
    setError(null);
    saveMutation.mutate(form);
  };
  return {
    templateId,
    isEdit,
    isTransactionalView,
    form,
    setForm,
    segments: segmentsQuery.data ?? {},
    initialLoading: templateQuery.isLoading,
    loading: saveMutation.isPending,
    error:
      error ??
      (templateQuery.error
        ? getHttpErrorMessage(templateQuery.error, 'Impossible de charger le modèle.')
        : null),
    message,
    handleSubmit,
    navigate,
  };
};
