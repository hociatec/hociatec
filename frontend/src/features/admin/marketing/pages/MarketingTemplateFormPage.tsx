import { getHttpErrorMessage } from '@/shared/lib/httpClient';
import { useEffect, useMemo, useState } from 'react';
import { useLocation, useNavigate, useParams } from 'react-router-dom';

import {
  createMarketingTemplate,
  fetchMarketingSegments,
  fetchMarketingTemplate,
  updateMarketingTemplate,
  type MarketingSegmentDefinition,
  type MarketingTemplatePayload,
} from '@/features/admin/marketing/api';
import { PageContainer } from '@/shared/components/PageContainer';
import { FeedbackMessage, LoadingState } from '@/shared/components/ui/page-state';
import { useDocumentTitle } from '@/shared/hooks/useDocumentTitle';

type FormState = MarketingTemplatePayload;

const emptyForm: FormState = {
  name: '',
  slug: '',
  scenarioKey: 'order_created',
  subjectTemplate: 'Commande {{order_number}} enregistrée',
  htmlBody: '<p>Bonjour {{first_name}},</p><p>Votre message ici.</p>',
  textBody: 'Bonjour {{first_name}},\n\nVotre message ici.',
  isActive: true,
};

export const MarketingTemplateFormPage = () => {
  const { templateId } = useParams();
  const isEdit = useMemo(() => Boolean(templateId), [templateId]);
  const location = useLocation();
  const isTransactionalView = location.pathname.startsWith('/admin/transactional-emails');
  useDocumentTitle(
    isEdit
      ? isTransactionalView ? 'Admin - Modifier un e-mail transactionnel' : 'Admin - Modifier un modèle d’e-mail'
      : isTransactionalView ? 'Admin - Nouvel e-mail transactionnel' : 'Admin - Nouveau modèle d’e-mail',
  );
  const navigate = useNavigate();
  const [form, setForm] = useState<FormState>(emptyForm);
  const [segments, setSegments] = useState<Record<string, MarketingSegmentDefinition>>({});
  const [initialLoading, setInitialLoading] = useState(false);
  const [loading, setLoading] = useState(false);
  const [error, setError] = useState<string | null>(null);
  const [message, setMessage] = useState<string | null>(null);

  useEffect(() => {
    void fetchMarketingSegments(isTransactionalView ? 'transactional' : 'templates').then(setSegments).catch(() => undefined);
  }, [isTransactionalView]);

  useEffect(() => {
    if (!isEdit || !templateId) return;
    setInitialLoading(true);
    setError(null);
    void fetchMarketingTemplate(Number(templateId))
      .then((template) => {
        setForm({
          name: template.name,
          slug: template.slug,
          scenarioKey: template.scenarioKey,
          subjectTemplate: template.subjectTemplate,
          htmlBody: template.htmlBody,
          textBody: template.textBody ?? '',
          isActive: template.isActive,
        });
      })
      .catch((err) => setError(getHttpErrorMessage(err, 'Impossible de charger le modèle.')))
      .finally(() => setInitialLoading(false));
  }, [isEdit, templateId]);

  const handleSubmit = async (event: React.FormEvent) => {
    event.preventDefault();
    if (!form.name.trim() || !form.slug.trim() || !form.subjectTemplate.trim() || !form.htmlBody.trim()) {
      setError('Veuillez renseigner tous les champs obligatoires.');
      return;
    }

    setLoading(true);
    setError(null);
    setMessage(null);

    try {
      if (isEdit && templateId) {
        await updateMarketingTemplate(Number(templateId), form);
        setMessage('Modèle mis à jour.');
      } else {
        await createMarketingTemplate(form);
        setMessage('Modèle créé.');
      }
      setTimeout(() => navigate(isTransactionalView ? '/admin/transactional-emails' : '/admin/marketing/templates'), 500);
    } catch (err) {
      setError(getHttpErrorMessage(err, 'Enregistrement impossible.'));
    } finally {
      setLoading(false);
    }
  };

  return (
    <PageContainer size="admin"
      title={isEdit ? (isTransactionalView ? 'Modifier un e-mail transactionnel' : 'Modifier un modèle d’e-mail') : (isTransactionalView ? 'Nouvel e-mail transactionnel' : 'Nouveau modèle d’e-mail')}
      headerActions={
        <div className="flex flex-wrap gap-3">
          <button
            type="button"
            className="catalog-admin-actions__edit"
            onClick={() => navigate(isTransactionalView ? '/admin/transactional-emails' : '/admin/marketing/templates')}
          >
            Retour à la liste
          </button>
          {isEdit && templateId ? (
            <button
              type="button"
              className="catalog-admin-actions__edit"
              onClick={() => navigate(isTransactionalView ? `/admin/transactional-emails/${templateId}` : `/admin/marketing/templates/${templateId}`)}
            >
              Voir le détail
            </button>
          ) : null}
        </div>
      }
    >
      {error && <FeedbackMessage>{error}</FeedbackMessage>}
      {message && <FeedbackMessage variant="success">{message}</FeedbackMessage>}

      {initialLoading ? (
        <LoadingState>Chargement du modèle...</LoadingState>
      ) : (
        <form onSubmit={handleSubmit} className="register-form-card form-card-grid">
          <label className="register-form__field">
            <span className="register-form__label">Nom</span>
            <input className="register-form__input" value={form.name} onChange={(event) => setForm((prev) => ({ ...prev, name: event.target.value }))} />
          </label>

          <label className="register-form__field">
            <span className="register-form__label">Slug</span>
            <input className="register-form__input" value={form.slug} onChange={(event) => setForm((prev) => ({ ...prev, slug: event.target.value }))} />
          </label>

          <label className="register-form__field">
            <span className="register-form__label">Scénario</span>
            <select
              className="register-form__input"
              value={form.scenarioKey}
              onChange={(event) => setForm((prev) => ({ ...prev, scenarioKey: event.target.value }))}
            >
              <optgroup label="Emails transactionnels">
                {Object.entries(segments)
                  .filter(([, segment]) => segment.type === 'transactional')
                  .map(([key, segment]) => (
                    <option key={key} value={key}>
                      {segment.label}
                    </option>
                  ))}
              </optgroup>
              <optgroup label="Templates marketing">
                {Object.entries(segments)
                  .filter(([, segment]) => segment.type !== 'transactional')
                  .map(([key, segment]) => (
                    <option key={key} value={key}>
                      {segment.label}
                    </option>
                  ))}
              </optgroup>
            </select>
          </label>

          {segments[form.scenarioKey] ? (
            <div className="rounded-2xl border border-brand-100 bg-brand-50 px-4 py-4 text-sm text-stone-700">
              <strong className="block text-brand-900">{segments[form.scenarioKey].label}</strong>
              <span>{segments[form.scenarioKey].description}</span>
            </div>
          ) : null}

          <label className="register-form__field">
            <span className="register-form__label">Objet</span>
            <input className="register-form__input" value={form.subjectTemplate} onChange={(event) => setForm((prev) => ({ ...prev, subjectTemplate: event.target.value }))} />
          </label>

          <label className="register-form__field">
            <span className="register-form__label">HTML</span>
            <textarea className="register-form__input" rows={10} value={form.htmlBody} onChange={(event) => setForm((prev) => ({ ...prev, htmlBody: event.target.value }))} />
          </label>

          <label className="register-form__field">
            <span className="register-form__label">Texte brut</span>
            <textarea className="register-form__input" rows={8} value={form.textBody ?? ''} onChange={(event) => setForm((prev) => ({ ...prev, textBody: event.target.value }))} />
          </label>

          <label className="booking__checkbox">
            <input type="checkbox" checked={form.isActive} onChange={(event) => setForm((prev) => ({ ...prev, isActive: event.target.checked }))} />
            Template actif
          </label>

          <p className="text-sm text-stone-500">
            Variables disponibles selon scénario: {'{{first_name}}'}, {'{{last_name}}'}, {'{{full_name}}'}, {'{{email}}'}, {'{{activation_url}}'}, {'{{activation_expires_in}}'}, {'{{password_reset_url}}'}, {'{{password_reset_expires_in}}'}, {'{{order_number}}'}, {'{{order_status}}'}, {'{{order_status_label}}'}, {'{{order_email_status_title}}'}, {'{{order_payment_instruction}}'}, {'{{order_payment_next_step}}'}, {'{{quote_number}}'}, {'{{quote_total_eur}}'}, {'{{quote_valid_until}}'}, {'{{quote_detail_url}}'}, {'{{customer_name}}'}, {'{{order_origin_sentence}}'}, {'{{previous_order_status}}'}, {'{{previous_order_status_label}}'}, {'{{invoice_number}}'}, {'{{invoice_date}}'}, {'{{order_total_eur}}'}, {'{{order_created_at}}'}, {'{{billing_name}}'}, {'{{purchase_order_number}}'}, {'{{order_detail_url}}'}, {'{{orders_list_url}}'}, {'{{app_frontend_url}}'}, {'{{order_count}}'}, {'{{total_spent_eur}}'}, {'{{last_order_number}}'}, {'{{last_order_date}}'}, {'{{days_since_last_order}}'}, {'{{pending_reviews_count}}'}, {'{{voucher_name}}'}, {'{{voucher_code}}'}, {'{{voucher_description}}'}, {'{{voucher_discount_type}}'}, {'{{voucher_discount_value}}'}, {'{{voucher_value_label}}'}, {'{{voucher_starts_at}}'}, {'{{voucher_ends_at}}'}, {'{{voucher_is_active}}'}, {'{{shop_url}}'}, {'{{cart_url}}'}, {'{{product_name}}'}, {'{{product_summary}}'}, {'{{product_price_eur}}'}, {'{{product_url}}'}, {'{{contact_name}}'}, {'{{contact_email}}'}, {'{{contact_subject}}'}, {'{{contact_message}}'}, {'{{mailer_from}}'}.
          </p>

          <button className="register-form__submit" type="submit" disabled={loading}>
            {loading ? 'Enregistrement...' : isEdit ? 'Mettre à jour' : 'Créer'}
          </button>
        </form>
      )}
    </PageContainer>
  );
};
