import { useEffect, useMemo, useState } from 'react';
import { Link, useSearchParams } from 'react-router-dom';

import { useRequireAdmin } from '@/features/admin/hooks/useRequireAdmin';
import {
  fetchMarketingSegments,
  fetchMarketingTemplates,
  previewMarketingAudience,
  sendMarketingCampaign,
  type MarketingAudiencePreview,
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
  registeredDays: string;
  recentDays: string;
  minimumTotalCents: string;
  minimumPendingReviews: string;
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
  registeredDays: '30',
  recentDays: '30',
  minimumTotalCents: '50000',
  minimumPendingReviews: '2',
  subject: '',
  htmlBody: '',
  textBody: '',
};

const segmentAdvice: Record<string, string[]> = {
  all_verified_users: [
    'À utiliser pour une annonce globale, une nouveauté catalogue ou une information institutionnelle.',
    'Privilégiez un objet simple avec une promesse claire.',
    'Évitez de multiplier les offres dans un même message.',
  ],
  recent_verified_users: [
    'Proposez une première action simple: découverte, rendez-vous ou premier achat.',
    'Le bon angle consiste à rassurer sur la valeur du service.',
    'Une offre limitée dans le temps améliore souvent la conversion.',
  ],
  customers_with_orders: [
    'Travaillez le cross-sell ou la montée en gamme à partir de l’historique d’achat.',
    'Un rappel de confiance fonctionne mieux qu’un discours trop commercial.',
    'Ajoutez une recommandation produit ou service liée aux commandes précédentes.',
  ],
  loyal_customers: [
    'Réservez un ton plus exclusif: fidélité, avant-première, offre privée.',
    'Ces profils répondent bien aux avantages réservés et aux messages personnalisés.',
    'Mettez en avant la relation dans la durée, pas seulement la remise.',
  ],
  single_order_customers: [
    'Le principal objectif est d’obtenir une deuxième commande.',
    'Appuyez-vous sur l’expérience de la première commande pour relancer.',
    'Une offre complémentaire ou un accompagnement humain est souvent pertinent.',
  ],
  recent_customers: [
    'Idéal pour un suivi post-achat, un service complémentaire ou un rappel d’usage.',
    'N’envoyez pas trop vite une nouvelle offre purement commerciale.',
    'Le meilleur levier est souvent l’aide à la prise en main.',
  ],
  high_value_customers: [
    'Ces clients méritent un ton premium et un message plus sélectif.',
    'Privilégiez l’exclusivité, la priorité de traitement ou une offre sur mesure.',
    'Évitez les promotions génériques qui dégradent la perception de valeur.',
  ],
  customers_without_review: [
    'Le message doit être court, direct et centré sur le retour d’expérience.',
    'Rappelez que l’avis aide les autres clients et améliore le service.',
    'Une relance simple fonctionne mieux qu’une offre trop agressive.',
  ],
  customers_with_pending_reviews: [
    'Priorisez un ton de rappel bienveillant avec une action facile à comprendre.',
    'Mentionner le nombre d’avis restants augmente la clarté du message.',
    'Ne surchargez pas le mail: un seul objectif, déposer les avis.',
  ],
  inactive_customers: [
    'La réactivation fonctionne mieux avec une nouveauté ou un bénéfice concret.',
    'Rappelez la dernière relation commerciale pour recréer le contexte.',
    'Une échéance courte aide à faire revenir les profils tièdes.',
  ],
  verified_without_orders: [
    'Le but est de transformer l’inscription en premier passage à l’action.',
    'Montrez immédiatement quoi faire ensuite: découvrir, demander, réserver, commander.',
    'Réduisez la friction et évitez les messages trop longs.',
  ],
  verified_without_orders_recent: [
    'Fenêtre idéale pour une séquence de bienvenue ou de conversion rapide.',
    'Mettez en avant une première étape évidente et peu engageante.',
    'Le message doit être clair, rassurant et orienté action.',
  ],
};

export const MarketingCampaignFormPage = () => {
  useDocumentTitle('Admin - Nouvelle campagne email');
  const { isAdmin, loading: guardLoading } = useRequireAdmin();
  const [searchParams, setSearchParams] = useSearchParams();
  const [templates, setTemplates] = useState<MarketingTemplate[]>([]);
  const [segments, setSegments] = useState<Record<string, MarketingSegmentDefinition>>({});
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
    void Promise.all([fetchMarketingTemplates(), fetchMarketingSegments()])
      .then(([templatesList, segmentsList]) => {
        setTemplates(templatesList);
        setSegments(segmentsList);
      })
      .catch((err: any) => setError(err?.message ?? 'Impossible de charger le module marketing.'))
      .finally(() => setLoading(false));
  }, [isAdmin]);

  useEffect(() => {
    const templateId = searchParams.get('templateId');
    if (!templateId || templates.length === 0) return;

    const template = templates.find((item) => String(item.id) === templateId);
    if (!template) return;

    setForm((prev) => ({ ...prev, templateId }));
    setSearchParams((prev) => {
      const next = new URLSearchParams(prev);
      next.delete('templateId');
      return next;
    }, { replace: true });
  }, [searchParams, setSearchParams, templates]);

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
      name: prev.name || `Campagne - ${selectedTemplate.name}`,
    }));
  }, [selectedTemplate]);

  useEffect(() => {
    const defaults = segments[form.segmentKey]?.defaults;
    if (!defaults) return;

    setForm((prev) => ({
      ...prev,
      minimumOrders: defaults.minimumOrders !== undefined ? String(defaults.minimumOrders) : prev.minimumOrders,
      inactiveDays: defaults.inactiveDays !== undefined ? String(defaults.inactiveDays) : prev.inactiveDays,
      registeredDays: defaults.registeredDays !== undefined ? String(defaults.registeredDays) : prev.registeredDays,
      recentDays: defaults.recentDays !== undefined ? String(defaults.recentDays) : prev.recentDays,
      minimumTotalCents: defaults.minimumTotalCents !== undefined ? String(defaults.minimumTotalCents) : prev.minimumTotalCents,
      minimumPendingReviews: defaults.minimumPendingReviews !== undefined ? String(defaults.minimumPendingReviews) : prev.minimumPendingReviews,
    }));
  }, [form.segmentKey, segments]);

  const criteria = useMemo(() => {
    const next: Record<string, string | number | boolean> = {};

    if (form.segmentKey === 'customers_with_orders' || form.segmentKey === 'loyal_customers') {
      next.minimumOrders = Number.parseInt(form.minimumOrders, 10) || 1;
    }
    if (form.segmentKey === 'inactive_customers') {
      next.inactiveDays = Number.parseInt(form.inactiveDays, 10) || 90;
    }
    if (form.segmentKey === 'recent_verified_users' || form.segmentKey === 'verified_without_orders_recent') {
      next.registeredDays = Number.parseInt(form.registeredDays, 10) || 30;
    }
    if (form.segmentKey === 'recent_customers') {
      next.recentDays = Number.parseInt(form.recentDays, 10) || 30;
    }
    if (form.segmentKey === 'high_value_customers') {
      next.minimumTotalCents = Number.parseInt(form.minimumTotalCents, 10) || 50000;
    }
    if (form.segmentKey === 'customers_with_pending_reviews') {
      next.minimumPendingReviews = Number.parseInt(form.minimumPendingReviews, 10) || 2;
    }

    return next;
  }, [
    form.inactiveDays,
    form.minimumOrders,
    form.minimumPendingReviews,
    form.minimumTotalCents,
    form.recentDays,
    form.registeredDays,
    form.segmentKey,
  ]);

  const activeTemplates = useMemo(
    () => templates.filter((item) => item.isActive),
    [templates],
  );

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
      setMessage(`Campagne envoyée à ${sent.recipientsCount} destinataire(s).`);
    } catch (err: any) {
      setError(err?.message ?? 'Envoi impossible.');
    } finally {
      setSaving(false);
    }
  };

  if (guardLoading) {
    return <PageContainer title="Nouvelle campagne email"><p className="muted">Vérification des droits...</p></PageContainer>;
  }
  if (!isAdmin) {
    return <PageContainer title="Nouvelle campagne email"><div className="register-form__alert">Accès restreint aux administrateurs.</div></PageContainer>;
  }

  return (
    <PageContainer
      title="Nouvelle campagne email"
      headerActions={
        <div className="flex flex-wrap gap-3">
          <Link
            to="/admin/marketing"
            className="inline-flex items-center rounded-full border border-slate-300 bg-white px-4 py-2 text-sm font-semibold text-slate-700 transition hover:border-slate-400 hover:text-slate-900"
          >
            Retour aux campagnes
          </Link>
          <Link
            to="/admin/marketing/templates"
            className="inline-flex items-center rounded-full border border-slate-300 bg-white px-4 py-2 text-sm font-semibold text-slate-700 transition hover:border-slate-400 hover:text-slate-900"
          >
            Bibliothèque des templates
          </Link>
        </div>
      }
    >
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
        <div className="grid gap-8 lg:grid-cols-[minmax(0,1.15fr)_minmax(0,0.85fr)]">
          <form onSubmit={handleSend} className="register-form-card" style={{ display: 'grid', gap: 16 }}>
            <label className="register-form__field">
              <span className="register-form__label">Nom de campagne</span>
              <input className="register-form__input" value={form.name} onChange={(event) => setForm((prev) => ({ ...prev, name: event.target.value }))} />
            </label>

            <div className="grid gap-4 md:grid-cols-2">
              <label className="register-form__field">
                <span className="register-form__label">Template</span>
                <select className="register-form__input" value={form.templateId} onChange={(event) => setForm((prev) => ({ ...prev, templateId: event.target.value }))}>
                  <option value="">Sans template</option>
                  {activeTemplates.map((template) => (
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
            </div>

            <div className="rounded-2xl border border-slate-200 bg-slate-50 px-4 py-4 text-sm text-slate-700">
              <strong className="block text-slate-900">{segments[form.segmentKey]?.label ?? 'Audience marketing'}</strong>
              <span>{segments[form.segmentKey]?.description ?? 'Choisissez une audience pour afficher son comportement cible.'}</span>
            </div>

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

            {(form.segmentKey === 'recent_verified_users' || form.segmentKey === 'verified_without_orders_recent') && (
              <label className="register-form__field">
                <span className="register-form__label">Ancienneté maximale du compte en jours</span>
                <input className="register-form__input" type="number" min={7} value={form.registeredDays} onChange={(event) => setForm((prev) => ({ ...prev, registeredDays: event.target.value }))} />
              </label>
            )}

            {form.segmentKey === 'recent_customers' && (
              <label className="register-form__field">
                <span className="register-form__label">Commande au cours des X derniers jours</span>
                <input className="register-form__input" type="number" min={7} value={form.recentDays} onChange={(event) => setForm((prev) => ({ ...prev, recentDays: event.target.value }))} />
              </label>
            )}

            {form.segmentKey === 'high_value_customers' && (
              <label className="register-form__field">
                <span className="register-form__label">Montant cumulé minimum en centimes</span>
                <input className="register-form__input" type="number" min={1000} step={100} value={form.minimumTotalCents} onChange={(event) => setForm((prev) => ({ ...prev, minimumTotalCents: event.target.value }))} />
                <span className="text-xs text-slate-500">Exemple: 50000 = 500,00 EUR.</span>
              </label>
            )}

            {form.segmentKey === 'customers_with_pending_reviews' && (
              <label className="register-form__field">
                <span className="register-form__label">Nombre minimum d’avis en attente</span>
                <input className="register-form__input" type="number" min={1} value={form.minimumPendingReviews} onChange={(event) => setForm((prev) => ({ ...prev, minimumPendingReviews: event.target.value }))} />
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
              <h2 className="text-xl font-semibold text-slate-900">Leviers conseillés</h2>
              <div className="space-y-2 text-sm text-slate-600">
                {audienceAdvice.map((item) => (
                  <div key={item} className="rounded-2xl border border-slate-200 bg-slate-50 px-4 py-3">
                    {item}
                  </div>
                ))}
              </div>
            </div>

            <div className="register-form-card" style={{ display: 'grid', gap: 12 }}>
              <div className="flex items-center justify-between gap-3">
                <h2 className="text-xl font-semibold text-slate-900">Templates recommandés</h2>
                <Link to="/admin/marketing/templates" className="text-sm font-semibold text-slate-700 hover:text-slate-900">
                  Voir toute la bibliothèque
                </Link>
              </div>
              {templatesForSegment.length === 0 ? (
                <p className="text-sm text-slate-500">Aucun template actif n’est encore associé à cette audience.</p>
              ) : (
                <div className="space-y-3">
                  {templatesForSegment.slice(0, 4).map((template) => (
                    <div key={template.id} className="rounded-2xl border border-slate-200 px-4 py-4">
                      <div className="flex items-start justify-between gap-3">
                        <div>
                          <strong className="block text-slate-900">{template.name}</strong>
                          <div className="mt-1 text-sm text-slate-500">{template.subjectTemplate}</div>
                        </div>
                        <button
                          type="button"
                          className="rounded-full border border-slate-300 px-3 py-1 text-xs font-semibold text-slate-700 transition hover:border-slate-400 hover:text-slate-900"
                          onClick={() => setForm((prev) => ({ ...prev, templateId: String(template.id) }))}
                        >
                          Utiliser
                        </button>
                      </div>
                      <div className="mt-3 flex flex-wrap gap-3 text-sm">
                        <Link to={`/admin/marketing/templates/${template.id}`} className="font-semibold text-slate-700 hover:text-slate-900">
                          Voir le détail
                        </Link>
                        <Link to={`/admin/marketing/templates/${template.id}/edit`} className="font-semibold text-slate-500 hover:text-slate-700">
                          Modifier
                        </Link>
                      </div>
                    </div>
                  ))}
                </div>
              )}
            </div>
          </div>
        </div>
      )}
    </PageContainer>
  );
};
