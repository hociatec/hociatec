import { useEffect, useState } from 'react';
import { useLocation, useNavigate, useParams } from 'react-router-dom';
import {
  createMarketingTemplate,
  fetchMarketingSegments,
  fetchMarketingTemplate,
  updateMarketingTemplate,
  type MarketingSegmentDefinition,
  type MarketingTemplatePayload,
} from '../api';
import { getHttpErrorMessage } from '@/shared/lib/httpClient';
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
  const isEdit = Boolean(templateId);
  const isTransactionalView = location.pathname.startsWith('/admin/transactional-emails');
  const [form, setForm] = useState<MarketingTemplatePayload>(defaultMarketingTemplateForm);
  const [segments, setSegments] = useState<Record<string, MarketingSegmentDefinition>>({});
  const [initialLoading, setInitialLoading] = useState(false);
  const [loading, setLoading] = useState(false);
  const [error, setError] = useState<string | null>(null);
  const [message, setMessage] = useState<string | null>(null);
  useEffect(() => {
    void fetchMarketingSegments(isTransactionalView ? 'transactional' : 'templates')
      .then(setSegments)
      .catch(() => undefined);
  }, [isTransactionalView]);
  useEffect(() => {
    if (!isEdit || !templateId) return;
    setInitialLoading(true);
    void fetchMarketingTemplate(Number(templateId))
      .then((template) =>
        setForm({
          name: template.name,
          slug: template.slug,
          scenarioKey: template.scenarioKey,
          subjectTemplate: template.subjectTemplate,
          htmlBody: template.htmlBody,
          textBody: template.textBody ?? '',
          isActive: template.isActive,
        }),
      )
      .catch((e) => setError(getHttpErrorMessage(e, 'Impossible de charger le modèle.')))
      .finally(() => setInitialLoading(false));
  }, [isEdit, templateId]);
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
    setLoading(true);
    setError(null);
    try {
      if (isEdit && templateId) {
        const response = await updateMarketingTemplate(Number(templateId), form);
        setMessage(response.message ?? 'Le modèle d’e-mail a bien été mis à jour.');
      } else {
        const response = await createMarketingTemplate(form);
        setMessage(response.message ?? 'Le modèle d’e-mail a bien été créé.');
      }
      window.setTimeout(
        () =>
          navigate(
            isTransactionalView ? '/admin/transactional-emails' : '/admin/marketing/templates',
          ),
        500,
      );
    } catch (e) {
      setError(getHttpErrorMessage(e, 'Enregistrement impossible.'));
    } finally {
      setLoading(false);
    }
  };
  return {
    templateId,
    isEdit,
    isTransactionalView,
    form,
    setForm,
    segments,
    initialLoading,
    loading,
    error,
    message,
    handleSubmit,
    navigate,
  };
};
