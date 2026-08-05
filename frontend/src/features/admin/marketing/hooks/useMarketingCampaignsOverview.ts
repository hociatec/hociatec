import { useMemo } from 'react';
import { useQuery } from '@tanstack/react-query';
import {
  fetchMarketingCampaigns,
  fetchMarketingSegments,
  fetchMarketingTemplates,
} from '../api';
import { getHttpErrorMessage } from '@/shared/lib/httpClient';
import { adminMarketingQueryKeys } from '@/shared/lib/queryKeys';

export const useMarketingCampaignsOverview = () => {
  const overviewQuery = useQuery({
    queryKey: adminMarketingQueryKeys.overview(),
    queryFn: async () => {
      const [templates, segments, campaigns] = await Promise.all([
        fetchMarketingTemplates(),
        fetchMarketingSegments(),
        fetchMarketingCampaigns(),
      ]);
      return { templates, segments, campaigns };
    },
  });
  const templates = overviewQuery.data?.templates ?? [];
  const segments = overviewQuery.data?.segments ?? {};
  const campaigns = overviewQuery.data?.campaigns ?? [];
  const activeTemplates = useMemo(
    () => templates.filter((template) => template.isActive),
    [templates],
  );
  return {
    templates,
    segments,
    campaigns,
    loading: overviewQuery.isLoading,
    error: overviewQuery.error
      ? getHttpErrorMessage(overviewQuery.error, 'Impossible de charger le module marketing.')
      : null,
    activeTemplates,
    lastCampaign: campaigns[0] ?? null,
  };
};
