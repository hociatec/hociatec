import { useEffect, useMemo, useState } from 'react';
import { Link } from 'react-router-dom';

import { useRequireAdmin } from '@/features/admin/hooks/useRequireAdmin';
import {
  fetchMarketingCampaigns,
  fetchMarketingSegments,
  fetchMarketingTemplates,
  previewMarketingAudience,
  sendMarketingCampaign,
  type MarketingAudiencePreview,
  type MarketingCampaign,
  type MarketingSegmentDefinition,
  type MarketingTemplate,
} from '@/features/admin/marketing/api';
import { PageContainer } from '@/shared/components/PageContainer';
import { useDocumentTitle } from '@/shared/hooks/useDocumentTitle';

type FormState = {
  name: string;
  templateId: string;
  segmentKey: string;
  minimumOrders: string;
  inactiveDays: string;
  subject: string;
  htmlBody: string;
  textBody: string;
};

const emptyForm: FormState = {
  name: '',
  templateId: '',
  segmentKey: 'customers_without_review',
  minimumOrders: '3',
  inactiveDays: '90',
  subject: '',
  htmlBody: '',
  textBody: '',
};

export const MarketingCampaignsPage = () => {
  useDocumentTitle('Admin - Campagnes email');
  const { isAdmin, loading: guardLoading } = useRequireAdmin();
  const [templates, setTemplates] = useState<MarketingTemplate[]>([]);
  const [segments, setSegments] = useState<Record<string, MarketingSegmentDefinition>>({});
  const [campaigns, setCampaigns] = useState<MarketingCampaign[]>([]);
  const [preview, setPreview] = useState<MarketingAudiencePreview | null>(null);
  const [form, setForm] = useState<FormState>(emptyForm);
  const [loading, setLoading] = useState(true);
  const [saving, setSaving] = useState(false);
  const [previewLoading, setPreviewLoading] = useState(false);
  const [error, setError] = useState<string | null>(null);
  const [message, setMessage] = useState<string | null>(null);

  useEffect(() => {
    if (!isAdmin) return;
    setLoading(true);
    setError(null);
    void Promise.all([fetchMarketingTemplates(), fetchMarketingSegments(), fetchMarketingCampaigns()])
      .then(([templatesList, segmentsList, campaignsList]) => {
        setTemplates(templatesList);
        setSegments(segmentsList);
        setCampaigns(campaignsList);
      })
      .catch((err: any) => setError(err?.message ?? 'Impossible de charger le module marketing.'))
      .finally(() => setLoading(false));
  }, [isAdmin]);

  const selectedTemplate = useMemo(
    () => templates.find((item) => String(item.id) === form.templateId) ?? null,
    [templates, form.templateId],
  );

  useEffect(() => {
    if (!selectedTemplate) return;
    setForm((prev) => ({
      ...prev,
      subject: selectedTemplate.subjectTemplate,
      htmlBody: selectedTemplate.htmlBody,
      textBody: selectedTemplate.textBody ?? '',
      segmentKey: selectedTemplate.scenarioKey,
    }));
  }, [selectedTemplate]);

  const criteria = useMemo(() => {
    const next: Record<string, string | number | boolean> = {};
    if (form.segmentKey === 'customers_with_orders' || form.segmentKey === 'loyal_customers') {
      next.minimumOrders = Number.parseInt(form.minimumOrders, 10) || 1;
    }
    if (form.segmentKey === 'inactive_customers') {
      next.inactiveDays = Number.parseInt(form.inactiveDays, 10) || 90;
    }
    return next;
  }, [form.segmentKey, form.minimumOrders, form.inactiveDays]);

  const handlePreview = async () => {
    setPreviewLoading(true);
    setError(null);
    try {
      const result = await previewMarketingAudience(form.segmentKey, criteria);
      setPreview(result.preview);
      setSegments(result.segments);
    } catch (err: any) {
      setError(err?.message ?? 'Prévisualisation impossible.');
    } finally {
      setPreviewLoading(false);
    }
  };

  const handleSend = async (event: React.FormEvent) => {
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
      setCampaigns((prev) => [
        {
          id: sent.id,
          name: sent.name,
          recipientsCount: sent.recipientsCount,
          sentAt: sent.sentAt,
          criteria,
          segmentKey: form.segmentKey,
          subjectSnapshot: form.subject,
          createdByEmail: null,
          template: selectedTemplate ? { id: selectedTemplate.id, name: selectedTemplate.name } : null,
        },
        ...prev,
      ]);
      setMessage(`Campagne envoyée à ${sent.recipientsCount} destinataire(s).`);
    } catch (err: any) {
      setError(err?.message ?? 'Envoi impossible.');
    } finally {
      setSaving(false);
    }
  };

  if (guardLoading) {
    return <PageContainer title="Campagnes email"><p className="muted">Vérification des droits...</p></PageContainer>;
  }
  if (!isAdmin) {
    return <PageContainer title="Campagnes email"><div className="register-form__alert">Accès restreint aux administrateurs.</div></PageContainer>;
  }

  return (
    <PageContainer
      title="Campagnes email"
      headerActions={
        <Link
          to="/admin/marketing/templates"
          className="inline-flex items-center rounded-full bg-slate-900 px-4 py-2 text-sm font-semibold text-white transition hover:bg-slate-800"
        >
          Gérer les templates
        </Link>
      }
    >
      <div className="mb-6 space-y-1">
        <p className="text-sm text-slate-600">
          Créez des campagnes ciblées selon le comportement des utilisateurs.
        </p>
        <p className="text-sm text-slate-500">
          Exemples rentables: relance d’avis, réactivation client, offre fidélité, conversion des comptes sans commande.
        </p>
      </div>

      {error && <div className="register-form__alert">{error}</div>}
      {message && (
        <div className="register-form__alert" style={{ background: '#ecfdf5', color: '#047857' }}>
          {message}
        </div>
      )}

      {loading ? (
        <div className="rounded-2xl border border-dashed border-slate-200 bg-slate-50 px-4 py-8 text-center text-slate-600">
          Chargement...
        </div>
      ) : (
        <div className="grid gap-8 lg:grid-cols-[minmax(0,1.1fr)_minmax(0,0.9fr)]">
          <form onSubmit={handleSend} className="register-form-card" style={{ display: 'grid', gap: 16 }}>
            <label className="register-form__field">
              <span className="register-form__label">Nom de campagne</span>
              <input className="register-form__input" value={form.name} onChange={(event) => setForm((prev) => ({ ...prev, name: event.target.value }))} />
            </label>

            <label className="register-form__field">
              <span className="register-form__label">Template</span>
              <select className="register-form__input" value={form.templateId} onChange={(event) => setForm((prev) => ({ ...prev, templateId: event.target.value }))}>
                <option value="">Sans template</option>
                {templates.filter((item) => item.isActive).map((template) => (
                  <option key={template.id} value={template.id}>
                    {template.name}
                  </option>
                ))}
              </select>
            </label>

            <label className="register-form__field">
              <span className="register-form__label">Audience</span>
              <select className="register-form__input" value={form.segmentKey} onChange={(event) => setForm((prev) => ({ ...prev, segmentKey: event.target.value }))}>
                {Object.entries(segments).map(([key, segment]) => (
                  <option key={key} value={key}>
                    {segment.label}
                  </option>
                ))}
              </select>
            </label>

            {(form.segmentKey === 'customers_with_orders' || form.segmentKey === 'loyal_customers') && (
              <label className="register-form__field">
                <span className="register-form__label">Nombre minimum de commandes</span>
                <input className="register-form__input" type="number" min={1} value={form.minimumOrders} onChange={(event) => setForm((prev) => ({ ...prev, minimumOrders: event.target.value }))} />
              </label>
            )}

            {form.segmentKey === 'inactive_customers' && (
              <label className="register-form__field">
                <span className="register-form__label">Inactivité en jours</span>
                <input className="register-form__input" type="number" min={30} value={form.inactiveDays} onChange={(event) => setForm((prev) => ({ ...prev, inactiveDays: event.target.value }))} />
              </label>
            )}

            <label className="register-form__field">
              <span className="register-form__label">Objet</span>
              <input className="register-form__input" value={form.subject} onChange={(event) => setForm((prev) => ({ ...prev, subject: event.target.value }))} />
            </label>

            <label className="register-form__field">
              <span className="register-form__label">HTML</span>
              <textarea className="register-form__input" rows={10} value={form.htmlBody} onChange={(event) => setForm((prev) => ({ ...prev, htmlBody: event.target.value }))} />
            </label>

            <label className="register-form__field">
              <span className="register-form__label">Texte brut</span>
              <textarea className="register-form__input" rows={6} value={form.textBody} onChange={(event) => setForm((prev) => ({ ...prev, textBody: event.target.value }))} />
            </label>

            <div className="flex flex-wrap gap-3">
              <button type="button" className="register-form__submit" style={{ background: '#e5e7eb', color: '#111827' }} onClick={() => void handlePreview()} disabled={previewLoading}>
                {previewLoading ? 'Prévisualisation...' : 'Prévisualiser l’audience'}
              </button>
              <button type="submit" className="register-form__submit" disabled={saving}>
                {saving ? 'Envoi...' : 'Envoyer la campagne'}
              </button>
            </div>
          </form>

          <div className="space-y-6">
            <div className="register-form-card" style={{ display: 'grid', gap: 12 }}>
              <h2 className="text-xl font-semibold text-slate-900">Audience</h2>
              <p className="text-sm text-slate-500">
                {segments[form.segmentKey]?.description ?? 'Choisissez une audience.'}
              </p>
              {preview ? (
                <>
                  <div className="rounded-2xl bg-slate-50 px-4 py-3 text-sm text-slate-700">
                    <strong>{preview.count}</strong> destinataire(s). {preview.description}
                  </div>
                  <div className="space-y-2">
                    {preview.recipients.map((recipient) => (
                      <div key={recipient.id} className="rounded-xl border border-slate-200 px-3 py-2 text-sm">
                        <strong>{recipient.fullName}</strong>
                        <div className="text-slate-500">{recipient.email}</div>
                      </div>
                    ))}
                  </div>
                </>
              ) : (
                <p className="text-sm text-slate-500">Lancez une prévisualisation pour voir le volume et quelques exemples.</p>
              )}
            </div>

            <div className="register-form-card" style={{ display: 'grid', gap: 12 }}>
              <h2 className="text-xl font-semibold text-slate-900">Suggestions utiles</h2>
              <ul className="list-disc pl-5 text-sm text-slate-600">
                <li>Relance d’avis pour les clients sans retour après commande.</li>
                <li>Réactivation à 90 jours pour les anciens clients inactifs.</li>
                <li>Offre fidélité pour les clients avec plusieurs commandes.</li>
                <li>Offre de bienvenue pour les comptes vérifiés sans commande.</li>
              </ul>
            </div>
          </div>
        </div>
      )}

      <div className="mt-10">
        <h2 className="mb-4 text-xl font-semibold text-slate-900">Historique des campagnes</h2>
        {campaigns.length === 0 ? (
          <div className="rounded-2xl border border-dashed border-slate-200 bg-slate-50 px-4 py-8 text-center text-slate-600">
            Aucune campagne envoyée.
          </div>
        ) : (
          <div className="overflow-hidden rounded-3xl border border-slate-200 bg-white shadow-sm">
            <table className="catalog-admin-table">
              <thead>
                <tr>
                  <th>Campagne</th>
                  <th>Audience</th>
                  <th>Destinataires</th>
                  <th>Envoyée le</th>
                </tr>
              </thead>
              <tbody>
                {campaigns.map((campaign) => (
                  <tr key={campaign.id}>
                    <td>
                      <strong>{campaign.name}</strong>
                      <div className="muted">{campaign.template?.name ?? campaign.subjectSnapshot}</div>
                    </td>
                    <td>{segments[campaign.segmentKey]?.label ?? campaign.segmentKey}</td>
                    <td>{campaign.recipientsCount}</td>
                    <td>{new Date(campaign.sentAt).toLocaleString('fr-FR')}</td>
                  </tr>
                ))}
              </tbody>
            </table>
          </div>
        )}
      </div>
    </PageContainer>
  );
};
