import { useState } from 'react';
import { useSearchParams } from 'react-router';
import { useMutation, useQuery, useQueryClient } from '@tanstack/react-query';

import {
  createBugReportComment,
  fetchBetaCampaigns,
  fetchBugReportComments,
  fetchMyBetaProfile,
  fetchMyBugReport,
  fetchMyBugReports,
  type BetaCampaign,
} from '../api/betaApi';
import { isCampaignOpenForReports } from '../components/dashboard/betaDashboardUtils';
import { useToast } from '@/shared/components/ui/toast';

export const useBetaDashboardController = () => {
  const queryClient = useQueryClient();
  const toast = useToast();
  const [searchParams, setSearchParams] = useSearchParams();
  const [selectedReportId, setSelectedReportId] = useState<number | null>(null);
  const [selectedCampaign, setSelectedCampaign] = useState<BetaCampaign | null>(null);
  const [viewedCampaign, setViewedCampaign] = useState<BetaCampaign | null>(null);
  const [newCommentText, setNewCommentText] = useState('');
  const [isCreateReportOpen, setIsCreateReportOpen] = useState(false);
  const [reportPage, setReportPage] = useState(1);
  const [commentPage, setCommentPage] = useState(1);

  const {
    data: profile,
    isLoading: isLoadingProfile,
    isError: isProfileError,
  } = useQuery<Record<string, unknown>>({
    queryKey: ['betaProfile'],
    queryFn: fetchMyBetaProfile,
    retry: false,
  });

  const { data: campaigns = [], error: campaignsError } = useQuery({
    queryKey: ['betaCampaigns'],
    queryFn: fetchBetaCampaigns,
    enabled: Boolean(profile),
  });

  const { data: reportsResult, error: reportsError } = useQuery({
    queryKey: ['betaReports', reportPage],
    queryFn: () => fetchMyBugReports({ page: reportPage, perPage: 12 }),
    enabled: Boolean(profile),
  });

  const reports = reportsResult?.items ?? [];
  const reportsMeta = reportsResult?.meta ?? null;
  const requestedReportId = Number(searchParams.get('reportId') ?? 0) || null;
  const effectiveSelectedReportId = selectedReportId ?? requestedReportId;

  const { data: selectedReport } = useQuery({
    queryKey: ['betaReport', effectiveSelectedReportId],
    queryFn: () => fetchMyBugReport(effectiveSelectedReportId!),
    enabled: effectiveSelectedReportId !== null && Boolean(profile),
  });

  const { data: commentsResult, isLoading: loadingComments } = useQuery({
    queryKey: ['myBugReportComments', effectiveSelectedReportId, commentPage],
    queryFn: () => fetchBugReportComments(effectiveSelectedReportId!, commentPage),
    enabled: effectiveSelectedReportId !== null && Boolean(profile),
  });

  const postCommentMutation = useMutation({
    mutationFn: () => createBugReportComment(effectiveSelectedReportId!, newCommentText),
    onSuccess: () => {
      setNewCommentText('');
      queryClient.invalidateQueries({ queryKey: ['myBugReportComments', effectiveSelectedReportId] });
      queryClient.invalidateQueries({ queryKey: ['betaReports'] });
      toast.show('Votre message a bien été envoyé.', { variant: 'success' });
    },
    onError: (err) => {
      toast.show(err instanceof Error ? err.message : 'Erreur lors de l\'envoi.', { variant: 'error' });
    },
  });

  const handlePostComment = (event: React.FormEvent) => {
    event.preventDefault();
    if (!newCommentText.trim()) return;
    postCommentMutation.mutate();
  };

  const openCampaignReport = (campaign: BetaCampaign) => {
    setSelectedCampaign(campaign);
    setViewedCampaign(null);
    setIsCreateReportOpen(true);
  };

  const openReportFollowUp = (id: number) => {
    setSelectedReportId(id);
    setCommentPage(1);
    setSearchParams({ reportId: String(id) });
  };

  const closeReportFollowUp = () => {
    setSelectedReportId(null);
    setCommentPage(1);
    setSearchParams({});
  };

  const error = campaignsError || reportsError;
  const profileStatus = typeof profile?.status === 'string' ? profile.status : '';
  const canReport = profileStatus === 'accepted';
  const activeReport = reports.find((report) => report.id === effectiveSelectedReportId) ?? selectedReport;
  const viewedCampaignReports = viewedCampaign
    ? reports.filter((report) => report.campaignId === viewedCampaign.id)
    : [];
  const viewedCampaignCanReport = Boolean(viewedCampaign && canReport && isCampaignOpenForReports(viewedCampaign));

  return {
    activeReport,
    canReport,
    campaigns,
    commentPage,
    comments: commentsResult?.items ?? [],
    commentsMeta: commentsResult?.meta ?? null,
    effectiveSelectedReportId,
    errorMessage: error instanceof Error ? error.message : error ? 'Impossible de charger vos données.' : null,
    isCreateReportOpen,
    isLoadingProfile,
    isProfileError,
    loadingComments,
    newCommentText,
    openReports: reports.filter((report) => !['resolved', 'duplicate', 'rejected'].includes(report.status)).length,
    postCommentPending: postCommentMutation.isPending,
    profile,
    profileStatus,
    reportPage,
    reportsMeta,
    resolvedReports: reports.filter((report) => report.status === 'resolved').length,
    selectedCampaign,
    viewedCampaign,
    viewedCampaignCanReport,
    viewedCampaignReports,
    closeReportFollowUp,
    handlePostComment,
    openCampaignReport,
    openReportFollowUp,
    setCommentPage,
    setIsCreateReportOpen,
    setNewCommentText,
    setReportPage,
    setSelectedCampaign,
    setViewedCampaign,
  };
};
