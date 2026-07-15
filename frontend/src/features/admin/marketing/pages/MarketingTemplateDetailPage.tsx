import { useEffect, useState } from 'react';
import { Link, useParams } from 'react-router-dom';

import { useRequireAdmin } from '@/features/admin/hooks/useRequireAdmin';
import { fetchMarketingSegments, fetchMarketingTemplate, type MarketingTemplate } from '@/features/admin/marketing/api';
import { PageContainer } from '@/shared/components/PageContainer';
import { useDocumentTitle } from '@/shared/hooks/useDocumentTitle';

const availableVariables = [
  '{{first_name}}',
  '{{last_name}}',
  '{{full_name}}',
  '{{email}}',
  '{{order_count}}',
  '{{total_spent_eur}}',
  '{{last_order_number}}',
  '{{last_order_date}}',
  '{{days_since_last_order}}',
  '{{pending_reviews_count}}',
  '{{app_frontend_url}}',
];

export const MarketingTemplateDetailPage = () => {
  useDocumentTitle('Admin - Détail template email');
  const { templateId } = useParams();
  const { isAdmin, loading: guardLoading } = useRequireAdmin();
  const [template, setTemplate] = useState<MarketingTemplate | null>(null);
  const [segments, setSegments] = useState<Record<string, { label: string; description: string }>>({});
  const [loading, setLoading] = useState(true);
  const [error, setError] = useState<string | null>(null);

  useEffect(() => {
    if (!isAdmin || !templateId) return;
    setLoading(true);
    setError(null);
    void Promise.all([fetchMarketingTemplate(Number(templateId)), fetchMarketingSegments()])
      .then(([templateItem, segmentsList]) => {
        setTemplate(templateItem);
        setSegments(segmentsList);
      })
      .catch((err: any) => setError(err?.message ?? 'Impossible de charger le template.'))
      .finally(() => setLoading(false));
  }, [isAdmin, templateId]);

  if (guardLoading) {
    return <PageContainer title="Template email"><p className="muted">Vérification des droits...</p></PageContainer>;
  }
  if (!isAdmin) {
    return <PageContainer title="Template email"><div className="register-form__alert">Accès restreint aux administrateurs.</div></PageContainer>;
  }

  return (
    <PageContainer
      title={template ? template.name : 'Détail template email'}
      headerActions={
        <div className="flex flex-wrap gap-3">
          <Link
            to="/admin/marketing/templates"
            className="inline-flex items-center rounded-full border border-slate-300 bg-white px-4 py-2 text-sm font-semibold text-slate-700 transition hover:border-slate-400 hover:text-slate-900"
          >
            Retour à la bibliothèque
          </Link>
          {template && (
            <>
              <Link
                to={`/admin/marketing/new?templateId=${template.id}`}
                className="inline-flex items-center rounded-full border border-slate-300 bg-white px-4 py-2 text-sm font-semibold text-slate-700 transition hover:border-slate-400 hover:text-slate-900"
              >
                Utiliser en campagne
              </Link>
              <Link
                to={`/admin/marketing/templates/${template.id}/edit`}
                className="inline-flex items-center rounded-full bg-slate-900 px-4 py-2 text-sm font-semibold text-white transition hover:bg-slate-800"
              >
                Modifier
              </Link>
            </>
          )}
        </div>
      }
    >
      {error && <div className="register-form__alert">{error}</div>}

      {loading ? (
        <div className="rounded-2xl border border-dashed border-slate-200 bg-slate-50 px-4 py-8 text-center text-slate-600">
          Chargement du template...
        </div>
      ) : !template ? (
        <div className="rounded-2xl border border-dashed border-slate-200 bg-slate-50 px-4 py-8 text-center text-slate-600">
          Template introuvable.
        </div>
      ) : (
        <div className="space-y-8">
          <div className="grid gap-4 md:grid-cols-4">
            <div className="rounded-3xl border border-slate-200 bg-white px-5 py-4 shadow-sm">
              <div className="text-xs font-semibold uppercase tracking-[0.18em] text-slate-400">Scénario</div>
              <div className="mt-2 text-sm font-semibold text-slate-900">{segments[template.scenarioKey]?.label ?? template.scenarioKey}</div>
            </div>
            <div className="rounded-3xl border border-slate-200 bg-white px-5 py-4 shadow-sm">
              <div className="text-xs font-semibold uppercase tracking-[0.18em] text-slate-400">Statut</div>
              <div className="mt-2 text-sm font-semibold text-slate-900">{template.isActive ? 'Actif' : 'Désactivé'}</div>
            </div>
            <div className="rounded-3xl border border-slate-200 bg-white px-5 py-4 shadow-sm">
              <div className="text-xs font-semibold uppercase tracking-[0.18em] text-slate-400">Créé le</div>
              <div className="mt-2 text-sm font-semibold text-slate-900">{template.createdAt ? new Date(template.createdAt).toLocaleDateString('fr-FR') : '-'}</div>
            </div>
            <div className="rounded-3xl border border-slate-200 bg-white px-5 py-4 shadow-sm">
              <div className="text-xs font-semibold uppercase tracking-[0.18em] text-slate-400">Mis à jour</div>
              <div className="mt-2 text-sm font-semibold text-slate-900">{template.updatedAt ? new Date(template.updatedAt).toLocaleDateString('fr-FR') : '-'}</div>
            </div>
          </div>

          <div className="grid gap-8 lg:grid-cols-[minmax(0,1fr)_360px]">
            <div className="space-y-6">
              <div className="rounded-3xl border border-slate-200 bg-white p-6 shadow-sm">
                <h2 className="text-xl font-semibold text-slate-900">Informations</h2>
                <div className="mt-4 grid gap-4 md:grid-cols-2">
                  <div>
                    <div className="text-xs font-semibold uppercase tracking-[0.18em] text-slate-400">Nom</div>
                    <div className="mt-2 text-sm text-slate-800">{template.name}</div>
                  </div>
                  <div>
                    <div className="text-xs font-semibold uppercase tracking-[0.18em] text-slate-400">Slug</div>
                    <div className="mt-2 text-sm text-slate-800">{template.slug}</div>
                  </div>
                  <div className="md:col-span-2">
                    <div className="text-xs font-semibold uppercase tracking-[0.18em] text-slate-400">Objet</div>
                    <div className="mt-2 text-sm text-slate-800">{template.subjectTemplate}</div>
                  </div>
                  <div className="md:col-span-2">
                    <div className="text-xs font-semibold uppercase tracking-[0.18em] text-slate-400">Description d’usage</div>
                    <div className="mt-2 text-sm text-slate-600">{segments[template.scenarioKey]?.description ?? 'Scénario métier associé au template.'}</div>
                  </div>
                </div>
              </div>

              <div className="rounded-3xl border border-slate-200 bg-white p-6 shadow-sm">
                <h2 className="text-xl font-semibold text-slate-900">Aperçu HTML</h2>
                <div className="mt-4 rounded-2xl border border-slate-200 bg-slate-50 p-5">
                  <div dangerouslySetInnerHTML={{ __html: template.htmlBody }} />
                </div>
              </div>

              <div className="rounded-3xl border border-slate-200 bg-white p-6 shadow-sm">
                <h2 className="text-xl font-semibold text-slate-900">Version texte</h2>
                <pre className="mt-4 overflow-x-auto whitespace-pre-wrap rounded-2xl border border-slate-200 bg-slate-50 p-5 text-sm text-slate-700">
                  {template.textBody?.trim() || 'Aucune version texte enregistrée.'}
                </pre>
              </div>
            </div>

            <div className="space-y-6">
              <div className="rounded-3xl border border-slate-200 bg-white p-6 shadow-sm">
                <h2 className="text-xl font-semibold text-slate-900">Variables disponibles</h2>
                <div className="mt-4 flex flex-wrap gap-2">
                  {availableVariables.map((variable) => (
                    <span key={variable} className="rounded-full border border-slate-200 bg-slate-50 px-3 py-1 text-xs font-semibold text-slate-700">
                      {variable}
                    </span>
                  ))}
                </div>
              </div>

              <div className="rounded-3xl border border-slate-200 bg-white p-6 shadow-sm">
                <h2 className="text-xl font-semibold text-slate-900">Actions rapides</h2>
                <div className="mt-4 space-y-3 text-sm">
                  <Link to={`/admin/marketing/new?templateId=${template.id}`} className="block rounded-2xl border border-slate-200 bg-slate-50 px-4 py-3 font-semibold text-slate-700 transition hover:border-slate-300 hover:text-slate-900">
                    Créer une campagne avec ce template
                  </Link>
                  <Link to={`/admin/marketing/templates/${template.id}/edit`} className="block rounded-2xl border border-slate-200 bg-slate-50 px-4 py-3 font-semibold text-slate-700 transition hover:border-slate-300 hover:text-slate-900">
                    Modifier ce template
                  </Link>
                  <Link to="/admin/marketing/templates/new" className="block rounded-2xl border border-slate-200 bg-slate-50 px-4 py-3 font-semibold text-slate-700 transition hover:border-slate-300 hover:text-slate-900">
                    Dupliquer manuellement dans un nouveau template
                  </Link>
                </div>
              </div>
            </div>
          </div>
        </div>
      )}
    </PageContainer>
  );
};
