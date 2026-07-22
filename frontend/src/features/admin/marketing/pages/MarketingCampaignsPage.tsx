import { getHttpErrorMessage } from '@/shared/lib/httpClient';
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
import { AdminListState, AdminMetricCard, AdminMetricGrid, AdminTableShell } from '@/shared/components/admin/AdminDataView';
import { FeedbackMessage, PrimaryLink } from '@/shared/components/ui/page-state';
import { useDocumentTitle } from '@/shared/hooks/useDocumentTitle';
import { formatEuroCents, formatFrenchDateTime, formatOptionalFrenchDate } from '@/shared/lib/formatters';

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
      return formatEuroCents(Number(criteria.minimumTotalCents ?? 50000) || 50000);
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
      .catch((err) => setError(getHttpErrorMessage(err, 'Impossible de charger le module marketing.')))
      .finally(() => setLoading(false));
  }, []);

  const activeTemplates = useMemo(
    () => templates.filter((item) => item.isActive),
    [templates],
  );

  const lastCampaign = campaigns[0] ?? null;

  return (
    <PageContainer size="admin"
      title="Campagnes e-mail"
      headerActions={
        <div className="flex flex-wrap gap-3">
          <Link
            to="/admin/marketing/templates"
            className="inline-flex items-center rounded-full border border-brand-200 bg-white px-4 py-2 text-sm font-semibold text-stone-700 transition hover:border-brand-300 hover:text-brand-900"
          >
            Bibliothèque des modèles
          </Link>
          <PrimaryLink to="/admin/marketing/templates/new">
            Nouveau modèle
          </PrimaryLink>
          <Link
            to="/admin/marketing/new"
            className="inline-flex items-center rounded-full bg-brand-900 px-4 py-2 text-sm font-semibold text-white transition hover:bg-brand-800"
          >
            Créer une nouvelle campagne
          </Link>
        </div>
      }
    >
      <AdminMetricGrid columns={4}>
        <AdminMetricCard label="Modèles actifs" value={activeTemplates.length} />
        <AdminMetricCard label="Audiences disponibles" value={Object.keys(segments).length} />
        <AdminMetricCard label="Campagnes envoyées" value={campaigns.length} />
        <AdminMetricCard label="Dernier envoi" value={lastCampaign ? formatOptionalFrenchDate(lastCampaign.sentAt) : 'Aucun'} />
      </AdminMetricGrid>

      <div className="mb-6 space-y-1">
        <p className="text-sm text-stone-600">
          Activez vos relances marketing avec des audiences ciblées, des critères métier et des modèles réutilisables.
        </p>
        <p className="text-sm text-stone-500">
          L’espace permet maintenant de cibler l’acquisition, la réactivation, la fidélisation et la collecte d’avis depuis l’admin.
        </p>
      </div>

      {error && <FeedbackMessage>{error}</FeedbackMessage>}
      <AdminListState
        loading={loading}
        isEmpty={false}
        loadingLabel="Chargement..."
        emptyLabel=""
      >
        <div className="rounded-xl border border-brand-100 bg-white p-6 shadow-sm">
          <h2 className="text-xl font-semibold text-brand-900">Création de campagne</h2>
          <div className="mt-4 flex flex-wrap gap-3">
            <PrimaryLink to="/admin/marketing/new">
              Créer une nouvelle campagne
            </PrimaryLink>
            <Link
              to="/admin/marketing/templates"
              className="inline-flex items-center rounded-full border border-brand-200 bg-white px-4 py-2 text-sm font-semibold text-stone-700 transition hover:border-brand-300 hover:text-brand-900"
            >
              Parcourir les modèles
            </Link>
          </div>
        </div>
      </AdminListState>

      <div className="mt-10">
        <h2 className="mb-4 text-xl font-semibold text-brand-900">Historique des campagnes</h2>
        <AdminListState
          loading={loading}
          isEmpty={campaigns.length === 0}
          loadingLabel="Chargement..."
          emptyLabel="Aucune campagne envoyée."
        >
          <AdminTableShell>
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
                    <td>{formatFrenchDateTime(campaign.sentAt)}</td>
                  </tr>
                ))}
              </tbody>
            </table>
          </AdminTableShell>
        </AdminListState>
      </div>
    </PageContainer>
  );
};
