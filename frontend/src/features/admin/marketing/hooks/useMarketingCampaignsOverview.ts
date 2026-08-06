import { useMemo, useState } from 'react';
import { useQuery } from '@tanstack/react-query';
import {
  fetchMarketingCampaignsPage,
  fetchMarketingSegments,
  fetchMarketingTemplates,
} from '../api';
import { getHttpErrorMessage } from '@/shared/lib/httpClient';
import { adminMarketingQueryKeys } from '@/features/admin/marketing/queryKeys';

export const useMarketingCampaignsOverview = () => {
  const [campaignPage, setCampaignPage] = useState(1);
  const overviewQuery = useQuery({
    queryKey: [...adminMarketingQueryKeys.overview(), { campaignPage }],
    queryFn: async () => {
      const [templates, segments, campaigns] = await Promise.all([
        fetchMarketingTemplates(),
        fetchMarketingSegments(),
        fetchMarketingCampaignsPage(campaignPage, 10),
      ]);
      return { templates, segments, campaigns };
    },
  });
  const templates = overviewQuery.data?.templates ?? [];
  const segments = overviewQuery.data?.segments ?? {};
  const campaignsResult = overviewQuery.data?.campaigns;
  const campaigns = campaignsResult?.items ?? [];
  const campaignsMeta = campaignsResult?.meta ?? { page: campaignPage, perPage: 10, total: 0, totalPages: 1 };
  const activeTemplates = useMemo(
    () => templates.filter((template) => template.isActive),
    [templates],
  );
  return {
    templates,
    segments,
    campaigns,
    campaignsMeta,
    setCampaignPage,
    loading: overviewQuery.isLoading,
    error: overviewQuery.error
      ? getHttpErrorMessage(overviewQuery.error, 'Impossible de charger le module marketing.')
      : null,
    activeTemplates,
    lastCampaign: campaigns[0] ?? null,
  };
};
