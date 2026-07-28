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
import { BetaBugReportDialog } from '../components/BetaBugReportDialog';
import { BetaCampaignDetailsDialog } from '../components/dashboard/BetaCampaignDetailsDialog';
import { BetaCampaignsSection } from '../components/dashboard/BetaCampaignsSection';
import { BetaDashboardStats } from '../components/dashboard/BetaDashboardStats';
import { BetaEmptyProfileState } from '../components/dashboard/BetaEmptyProfileState';
import { BetaProfileSummary } from '../components/dashboard/BetaProfileSummary';
import { BetaReportFollowUpDialog } from '../components/dashboard/BetaReportFollowUpDialog';
import { isCampaignOpenForReports } from '../components/dashboard/betaDashboardUtils';
import { PageContainer } from '@/shared/components/layout/PageContainer';
import { SiteLayout } from '@/shared/components/layout/SiteLayout';
import { FeedbackMessage } from '@/shared/components/ui/page-state';
import { useToast } from '@/shared/components/ui/toast';

export const BetaDashboardPage = () => {
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

  if (isLoadingProfile) {
    return (
      <SiteLayout headerVariant="light">
        <PageContainer title="Mon espace bêta">
          <p className="py-8 text-stone-500">Chargement de votre espace bêta...</p>
        </PageContainer>
      </SiteLayout>
    );
  }

  if (isProfileError || !profile) {
    return (
      <SiteLayout headerVariant="light">
        <PageContainer title="Espace Bêta-Testeur">
          <BetaEmptyProfileState />
        </PageContainer>
      </SiteLayout>
    );
  }

  const error = campaignsError || reportsError;
  const errorMessage = error instanceof Error ? error.message : error ? 'Impossible de charger vos données.' : null;
  const activeReport = reports.find((report) => report.id === effectiveSelectedReportId) ?? selectedReport;
  const comments = commentsResult?.items ?? [];
  const commentsMeta = commentsResult?.meta ?? null;
  const profileStatus = typeof profile.status === 'string' ? profile.status : '';
  const canReport = profileStatus === 'accepted';
  const resolvedReports = reports.filter((report) => report.status === 'resolved').length;
  const openReports = reports.filter((report) => !['resolved', 'duplicate', 'rejected'].includes(report.status)).length;
  const viewedCampaignReports = viewedCampaign
    ? reports.filter((report) => report.campaignId === viewedCampaign.id)
    : [];
  const viewedCampaignCanReport = Boolean(viewedCampaign && canReport && isCampaignOpenForReports(viewedCampaign));

  return (
    <SiteLayout headerVariant="light">
      <PageContainer title="Mon espace bêta">
        {errorMessage && <FeedbackMessage>{errorMessage}</FeedbackMessage>}

        <BetaDashboardStats
          campaignsCount={campaigns.length}
          openReports={openReports}
          resolvedReports={resolvedReports}
        />

        <BetaProfileSummary canReport={canReport} profileStatus={profileStatus} />

        <BetaCampaignsSection
          campaigns={campaigns}
          canReport={canReport}
          onOpenCampaign={setViewedCampaign}
        />

        <BetaCampaignDetailsDialog
          canCreateReport={viewedCampaignCanReport}
          campaign={viewedCampaign}
          reports={viewedCampaignReports}
          onClose={() => setViewedCampaign(null)}
          onCreateReport={openCampaignReport}
          onOpenReport={(reportId) => {
            setViewedCampaign(null);
            openReportFollowUp(reportId);
          }}
        />

        {effectiveSelectedReportId !== null && activeReport && (
          <BetaReportFollowUpDialog
            commentPage={commentPage}
            comments={comments}
            commentsMeta={commentsMeta}
            loadingComments={loadingComments}
            newCommentText={newCommentText}
            open={effectiveSelectedReportId !== null}
            report={activeReport}
            sending={postCommentMutation.isPending}
            onClose={closeReportFollowUp}
            onCommentPageChange={setCommentPage}
            onCommentTextChange={setNewCommentText}
            onSubmit={handlePostComment}
          />
        )}

        <BetaBugReportDialog
          open={isCreateReportOpen}
          onClose={() => {
            setIsCreateReportOpen(false);
            setSelectedCampaign(null);
          }}
          campaignId={selectedCampaign?.id}
          campaignName={selectedCampaign?.name}
        />

        {reportsMeta && reportsMeta.totalPages > 1 && (
          <div className="mt-6 flex items-center justify-center gap-3">
            <button
              type="button"
              disabled={reportPage <= 1}
              onClick={() => setReportPage((page) => Math.max(1, page - 1))}
              className="rounded-lg border border-stone-200 px-4 py-2 text-sm font-semibold disabled:opacity-50"
            >
              Page précédente
            </button>
            <span className="text-sm text-stone-600">Page {reportsMeta.page} sur {reportsMeta.totalPages}</span>
            <button
              type="button"
              disabled={reportPage >= reportsMeta.totalPages}
              onClick={() => setReportPage((page) => Math.min(reportsMeta.totalPages, page + 1))}
              className="rounded-lg border border-stone-200 px-4 py-2 text-sm font-semibold disabled:opacity-50"
            >
              Page suivante
            </button>
          </div>
        )}
      </PageContainer>
    </SiteLayout>
  );
};
