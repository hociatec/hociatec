import { useQuery } from '@tanstack/react-query';

import { fetchAdminCampaigns, fetchBugReportComments } from '../api';
import { adminBetaQueryKeys } from '@/features/betaTest/publicApi';

export const useAdminCampaignQueries = ({
  selectedReportId,
  commentsPage,
  campaignsPage,
}: {
  selectedReportId: number | null;
  commentsPage: number;
  campaignsPage: number;
}) => {
  const { data: campaignsResult, isLoading, error } = useQuery({
    queryKey: [...adminBetaQueryKeys.campaigns(), { campaignsPage }],
    queryFn: () => fetchAdminCampaigns(campaignsPage, 10),
  });

  const { data: commentsResult, isLoading: loadingComments } = useQuery({
    queryKey: adminBetaQueryKeys.bugReportCommentsPage(selectedReportId, commentsPage),
    queryFn: () => fetchBugReportComments(selectedReportId!, commentsPage),
    enabled: selectedReportId !== null,
  });

  return {
    campaigns: campaignsResult?.items ?? [],
    campaignsMeta: campaignsResult?.meta ?? { page: campaignsPage, perPage: 10, total: 0, totalPages: 1 },
    comments: commentsResult?.items ?? [],
    commentsMeta: commentsResult?.meta ?? null,
    error,
    isLoading,
    loadingComments,
  };
};
