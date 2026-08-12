import { useState } from 'react';
import { useQuery } from '@tanstack/react-query';
import { useSearchParams } from 'react-router';
import {
  fetchMarketingCampaignsPage,
  fetchMarketingSegments,
  fetchMarketingTemplatesPage,
} from '../api';
import { getHttpErrorMessage } from '@/shared/lib/httpClient';
import { adminMarketingQueryKeys } from '@/features/admin/marketing/queryKeys';
import { parseNullablePositiveInteger } from '@/shared/lib/parsers';

export const useMarketingCampaignsOverview = () => {
  const [searchParams, setSearchParams] = useSearchParams();
  const [campaignPage, setCampaignPage] = useState(parseNullablePositiveInteger(searchParams.get('page')) ?? 1);
  const activeTemplatesQuery = useQuery({
    queryKey: [...adminMarketingQueryKeys.templates(), { page: 1, perPage: 1, status: 'active' }],
    queryFn: () => fetchMarketingTemplatesPage(1, 1, undefined, undefined, undefined, 'active'),
  });
  const segmentsQuery = useQuery({
    queryKey: adminMarketingQueryKeys.segments('campaigns'),
    queryFn: () => fetchMarketingSegments('campaigns'),
  });
  const campaignsQuery = useQuery({
    queryKey: [...adminMarketingQueryKeys.campaigns(), { page: campaignPage }],
    queryFn: () => fetchMarketingCampaignsPage(campaignPage, 10),
  });
  const segments = segmentsQuery.data ?? {};
  const campaignsResult = campaignsQuery.data;
  const campaigns = campaignsResult?.items ?? [];
  const campaignsMeta = campaignsResult?.meta ?? { page: campaignPage, perPage: 10, total: 0, totalPages: 1 };

  const updateCampaignPage = (updater: (page: number) => number) => {
    const nextPage = updater(campaignPage);
    setCampaignPage(nextPage);
    const next = new URLSearchParams(searchParams);
    if (nextPage > 1) next.set('page', String(nextPage));
    else next.delete('page');
    setSearchParams(next, { replace: true });
  };

  return {
    segments,
    campaigns,
    campaignsMeta,
    setCampaignPage: updateCampaignPage,
    loading: activeTemplatesQuery.isLoading || segmentsQuery.isLoading || campaignsQuery.isLoading,
    error:
      activeTemplatesQuery.error
        ? getHttpErrorMessage(activeTemplatesQuery.error, 'Impossible de charger le module marketing.')
        : segmentsQuery.error
          ? getHttpErrorMessage(segmentsQuery.error, 'Impossible de charger le module marketing.')
          : campaignsQuery.error
            ? getHttpErrorMessage(campaignsQuery.error, 'Impossible de charger le module marketing.')
            : null,
    activeTemplatesCount: activeTemplatesQuery.data?.meta.total ?? 0,
    lastCampaign: campaigns[0] ?? null,
  };
};
