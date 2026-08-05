import { useQuery } from '@tanstack/react-query';

import { fetchAdminCampaigns, fetchBugReportComments } from '../api';
import { adminBetaQueryKeys } from '@/shared/lib/queryKeys';

export const useAdminCampaignQueries = ({
  selectedReportId,
  commentsPage,
}: {
  selectedReportId: number | null;
  commentsPage: number;
}) => {
  const { data: campaigns = [], isLoading, error } = useQuery({
    queryKey: adminBetaQueryKeys.campaigns(),
    queryFn: fetchAdminCampaigns,
  });

  const { data: commentsResult, isLoading: loadingComments } = useQuery({
    queryKey: adminBetaQueryKeys.bugReportCommentsPage(selectedReportId, commentsPage),
    queryFn: () => fetchBugReportComments(selectedReportId!, commentsPage),
    enabled: selectedReportId !== null,
  });

  return {
    campaigns,
    comments: commentsResult?.items ?? [],
    commentsMeta: commentsResult?.meta ?? null,
    error,
    isLoading,
    loadingComments,
  };
};
