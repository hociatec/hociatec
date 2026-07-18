import { useEffect, useMemo, useState } from 'react';
import { Link } from 'react-router-dom';

import {
  fetchMarketingCampaigns,
  fetchMarketingSegments,
  fetchMarketingTemplates,
  type MarketingCampaign,
  type MarketingSegmentDefinition,
  type MarketingTemplate,
} from '@/features/admin/marketing/api';
import { PageContainer } from '@/shared/components/PageContainer';
import { useDocumentTitle } from '@/shared/hooks/useDocumentTitle';

const formatCampaignCriteria = (campaign: MarketingCampaign) => {
  const criteria = campaign.criteria ?? {};

  switch (campaign.segmentKey) {
    case 'customers_with_orders':
    case 'loyal_customers':
      return `min ${criteria.minimumOrders ?? 1} commande(s)`;
    case 'inactive_customers':
      return `${criteria.inactiveDays ?? 90} jours d'inactivité`;
    case 'recent_verified_users':
    case 'verified_without_orders_recent':
      return `inscrits depuis ${criteria.registeredDays ?? 30} jours`;
    case 'recent_customers':
      return `commande sous ${criteria.recentDays ?? 30} jours`;
    case 'high_value_customers':
      return `${((Number(criteria.minimumTotalCents ?? 50000) || 50000) / 100).toLocaleString('fr-FR', { minimumFractionDigits: 2, maximumFractionDigits: 2 })} EUR`;
    case 'customers_with_pending_reviews':
      return `${criteria.minimumPendingReviews ?? 2} avis en attente`;
    default:
      return 'Ciblage standard';
  }
};

export const MarketingCampaignsPage = () => {
  useDocumentTitle('Admin - Campagnes e-mail');
  const [templates, setTemplates] = useState<MarketingTemplate[]>([]);
  const [segments, setSegments] = useState<Record<string, MarketingSegmentDefinition>>({});
  const [campaigns, setCampaigns] = useState<MarketingCampaign[]>([]);
  const [loading, setLoading] = useState(true);
  const [error, setError] = useState<string | null>(null);

  useEffect(() => {
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
  }, []);

  const activeTemplates = useMemo(
    () => templates.filter((item) => item.isActive),
    [templates],
  );

  const lastCampaign = campaigns[0] ?? null;

  return (
    <PageContainer
      title="Campagnes e-mail"
      headerActions={
        <div className="flex flex-wrap gap-3">
          <Link
            to="/admin/marketing/templates"
            className="inline-flex items-center rounded-full border border-slate-300 bg-white px-4 py-2 text-sm font-semibold text-slate-700 transition hover:border-slate-400 hover:text-slate-900"
          >
            Bibliothèque des modèles
          </Link>
          <Link
            to="/admin/marketing/templates/new"
            className="inline-flex items-center rounded-full bg-slate-900 px-4 py-2 text-sm font-semibold text-white transition hover:bg-slate-800"
          >
            Nouveau modèle
          </Link>
          <Link
            to="/admin/marketing/new"
            className="inline-flex items-center rounded-full bg-sky-600 px-4 py-2 text-sm font-semibold text-white transition hover:bg-sky-700"
          >
            Créer une nouvelle campagne
          </Link>
        </div>
      }
    >
      <div className="mb-8 grid gap-4 md:grid-cols-4">
        <div className="rounded-3xl border border-slate-200 bg-white px-5 py-4 shadow-sm">
          <div className="text-xs font-semibold uppercase tracking-[0.18em] text-slate-400">Modèles actifs</div>
          <div className="mt-2 text-3xl font-semibold text-slate-900">{activeTemplates.length}</div>
        </div>
        <div className="rounded-3xl border border-slate-200 bg-white px-5 py-4 shadow-sm">
          <div className="text-xs font-semibold uppercase tracking-[0.18em] text-slate-400">Audiences disponibles</div>
          <div className="mt-2 text-3xl font-semibold text-slate-900">{Object.keys(segments).length}</div>
        </div>
        <div className="rounded-3xl border border-slate-200 bg-white px-5 py-4 shadow-sm">
          <div className="text-xs font-semibold uppercase tracking-[0.18em] text-slate-400">Campagnes envoyées</div>
          <div className="mt-2 text-3xl font-semibold text-slate-900">{campaigns.length}</div>
        </div>
        <div className="rounded-3xl border border-slate-200 bg-white px-5 py-4 shadow-sm">
          <div className="text-xs font-semibold uppercase tracking-[0.18em] text-slate-400">Dernier envoi</div>
          <div className="mt-2 text-sm font-semibold text-slate-900">
            {lastCampaign ? new Date(lastCampaign.sentAt).toLocaleDateString('fr-FR') : 'Aucun'}
          </div>
        </div>
      </div>

      <div className="mb-6 space-y-1">
        <p className="text-sm text-slate-600">
          Activez vos relances marketing avec des audiences ciblées, des critères métier et des modèles réutilisables.
        </p>
        <p className="text-sm text-slate-500">
          L’espace permet maintenant de cibler l’acquisition, la réactivation, la fidélisation et la collecte d’avis depuis l’admin.
        </p>
      </div>

      {error && <div className="register-form__alert">{error}</div>}
      {loading ? (
        <div className="rounded-2xl border border-dashed border-slate-200 bg-slate-50 px-4 py-8 text-center text-slate-600">
          Chargement...
        </div>
      ) : (
        <div className="rounded-3xl border border-slate-200 bg-white p-6 shadow-sm">
          <h2 className="text-xl font-semibold text-slate-900">Création de campagne</h2>
          <div className="mt-4 flex flex-wrap gap-3">
            <Link
              to="/admin/marketing/new"
              className="inline-flex items-center rounded-full bg-slate-900 px-4 py-2 text-sm font-semibold text-white transition hover:bg-slate-800"
            >
              Créer une nouvelle campagne
            </Link>
            <Link
              to="/admin/marketing/templates"
              className="inline-flex items-center rounded-full border border-slate-300 bg-white px-4 py-2 text-sm font-semibold text-slate-700 transition hover:border-slate-400 hover:text-slate-900"
            >
              Parcourir les modèles
            </Link>
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
          <div className="overflow-x-auto rounded-3xl border border-slate-200 bg-white shadow-sm">
            <table className="catalog-admin-table">
              <thead>
                <tr>
                  <th>Campagne</th>
                  <th>Audience</th>
                  <th>Critère</th>
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
                    <td>{formatCampaignCriteria(campaign)}</td>
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
