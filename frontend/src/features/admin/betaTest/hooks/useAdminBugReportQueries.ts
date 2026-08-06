import { useQuery } from '@tanstack/react-query';

import {
  fetchAdminBugReport,
  fetchAdminBugReportDashboard,
  fetchAdminBugReports,
  fetchBugReportActivity,
  fetchBugReportComments,
} from '../api';
import { adminBetaQueryKeys } from '@/features/betaTest/publicApi';

type BugReportFilters = {
  page: number;
  perPage: number;
  status?: string;
  severity?: string;
  search?: string;
  assignedTo?: string;
};

export const useAdminBugReportQueries = ({
  filters,
  selectedReportId,
  commentPage,
  activityPage,
}: {
  activityPage: number;
  filters: BugReportFilters;
  selectedReportId: number | null;
  commentPage: number;
}) => {
  const { data: dashboard } = useQuery({
    queryKey: adminBetaQueryKeys.bugReportDashboard(),
    queryFn: fetchAdminBugReportDashboard,
  });

  const { data: reportsResult, isLoading, error } = useQuery({
    queryKey: adminBetaQueryKeys.bugReportsList(filters),
    queryFn: () => fetchAdminBugReports(filters),
  });

  const reports = reportsResult?.items ?? [];

  const { data: selectedReport } = useQuery({
    queryKey: adminBetaQueryKeys.bugReport(selectedReportId),
    queryFn: () => fetchAdminBugReport(selectedReportId!),
    enabled: selectedReportId !== null,
  });

  const { data: commentsResult, isLoading: loadingComments } = useQuery({
    queryKey: adminBetaQueryKeys.bugReportCommentsPage(selectedReportId, commentPage),
    queryFn: () => fetchBugReportComments(selectedReportId!, commentPage),
    enabled: selectedReportId !== null,
  });

  const { data: activitiesResult } = useQuery({
    queryKey: [...adminBetaQueryKeys.bugReportActivity(selectedReportId), { activityPage }],
    queryFn: () => fetchBugReportActivity(selectedReportId!, activityPage),
    enabled: selectedReportId !== null,
  });

  return {
    activeReport: reports.find((report) => report.id === selectedReportId) ?? selectedReport,
    activities: activitiesResult?.items ?? [],
    activitiesMeta: activitiesResult?.meta ?? null,
    comments: commentsResult?.items ?? [],
    commentsMeta: commentsResult?.meta ?? null,
    dashboard,
    error,
    isLoading,
    loadingComments,
    meta: reportsResult?.meta ?? null,
    reports,
  };
};
