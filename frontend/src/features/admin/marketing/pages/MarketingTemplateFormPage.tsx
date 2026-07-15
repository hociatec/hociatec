import { useEffect, useMemo, useState } from 'react';
import { useNavigate, useParams } from 'react-router-dom';

import { useRequireAdmin } from '@/features/admin/hooks/useRequireAdmin';
import {
  createMarketingTemplate,
  fetchMarketingSegments,
  fetchMarketingTemplate,
  updateMarketingTemplate,
  type MarketingTemplatePayload,
} from '@/features/admin/marketing/api';
import { PageContainer } from '@/shared/components/PageContainer';
import { useDocumentTitle } from '@/shared/hooks/useDocumentTitle';

type FormState = MarketingTemplatePayload;

const emptyForm: FormState = {
  name: '',
  slug: '',
  scenarioKey: 'customers_without_review',
  subjectTemplate: '',
  htmlBody: '<p>Bonjour {{first_name}},</p><p>Votre message ici.</p>',
  textBody: 'Bonjour {{first_name}},\n\nVotre message ici.',
  isActive: true,
};

export const MarketingTemplateFormPage = () => {
  const { templateId } = useParams();
  const isEdit = useMemo(() => Boolean(templateId), [templateId]);
  useDocumentTitle(isEdit ? 'Admin - Modifier un template email' : 'Admin - Nouveau template email');
  const navigate = useNavigate();
  const { isAdmin, loading: guardLoading } = useRequireAdmin();
  const [form, setForm] = useState<FormState>(emptyForm);
  const [segments, setSegments] = useState<Record<string, { label: string; description: string }>>({});
  const [initialLoading, setInitialLoading] = useState(false);
  const [loading, setLoading] = useState(false);
  const [error, setError] = useState<string | null>(null);
  const [message, setMessage] = useState<string | null>(null);

  useEffect(() => {
    if (!isAdmin) return;
    void fetchMarketingSegments().then(setSegments).catch(() => undefined);
  }, [isAdmin]);

  useEffect(() => {
    if (!isAdmin || !isEdit || !templateId) return;
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
      .catch((err: any) => setError(err?.message ?? 'Impossible de charger le template.'))
      .finally(() => setInitialLoading(false));
  }, [isAdmin, isEdit, templateId]);

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
        setMessage('Template mis à jour.');
      } else {
        await createMarketingTemplate(form);
        setMessage('Template créé.');
      }
      setTimeout(() => navigate('/admin/marketing/templates'), 500);
    } catch (err: any) {
      setError(err?.message ?? 'Enregistrement impossible.');
    } finally {
      setLoading(false);
    }
  };

  if (guardLoading) {
    return <PageContainer title="Template email"><p className="muted">Vérification des droits...</p></PageContainer>;
  }
  if (!isAdmin) {
    return <PageContainer title="Template email"><div className="register-form__alert">Accès restreint aux administrateurs.</div></PageContainer>;
  }

  return (
    <PageContainer
      title={isEdit ? 'Modifier un template email' : 'Nouveau template email'}
      headerActions={
        <button
          type="button"
          className="register-form__submit"
          style={{ background: '#e5e7eb', color: '#111827' }}
          onClick={() => navigate('/admin/marketing/templates')}
        >
          Retour à la liste
        </button>
      }
    >
      {error && <div className="register-form__alert">{error}</div>}
      {message && (
        <div className="register-form__alert" style={{ background: '#ecfdf5', color: '#047857' }}>
          {message}
        </div>
      )}

      {initialLoading ? (
        <p className="muted">Chargement du template...</p>
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
            <span className="register-form__label">Scénario</span>
            <select
              className="register-form__input"
              value={form.scenarioKey}
              onChange={(event) => setForm((prev) => ({ ...prev, scenarioKey: event.target.value }))}
            >
              {Object.entries(segments).map(([key, segment]) => (
                <option key={key} value={key}>
                  {segment.label}
                </option>
              ))}
            </select>
          </label>

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

          <label style={{ display: 'flex', alignItems: 'center', gap: 8 }}>
            <input type="checkbox" checked={form.isActive} onChange={(event) => setForm((prev) => ({ ...prev, isActive: event.target.checked }))} />
            <span>Template actif</span>
          </label>

          <p className="text-sm text-slate-500">
            Variables disponibles: {'{{first_name}}'}, {'{{last_name}}'}, {'{{full_name}}'}, {'{{email}}'}, {'{{order_count}}'}, {'{{last_order_number}}'}, {'{{last_order_date}}'}, {'{{pending_reviews_count}}'}, {'{{app_frontend_url}}'}.
          </p>

          <button className="register-form__submit" type="submit" disabled={loading}>
            {loading ? 'Enregistrement...' : isEdit ? 'Mettre à jour' : 'Créer'}
          </button>
        </form>
      )}
    </PageContainer>
  );
};
