import { BetaBugReportDialog } from '../components/BetaBugReportDialog';
import { BetaCampaignDetailsDialog } from '../components/dashboard/BetaCampaignDetailsDialog';
import { BetaCampaignsSection } from '../components/dashboard/BetaCampaignsSection';
import { BetaDashboardStats } from '../components/dashboard/BetaDashboardStats';
import { BetaEmptyProfileState } from '../components/dashboard/BetaEmptyProfileState';
import { BetaProfileSummary } from '../components/dashboard/BetaProfileSummary';
import { BetaReportFollowUpDialog } from '../components/dashboard/BetaReportFollowUpDialog';
import { useBetaDashboardController } from '../hooks/useBetaDashboardController';
import { PageContainer } from '@/shared/components/layout/PageContainer';
import { SiteLayout } from '@/shared/components/layout/SiteLayout';
import { PaginationControls } from '@/shared/components/ui/PaginationControls';
import { FeedbackMessage } from '@/shared/components/ui/page-state';

export const BetaDashboardPage = () => {
  const dashboard = useBetaDashboardController();

  if (dashboard.isLoadingProfile) {
    return (
      <SiteLayout headerVariant="light">
        <PageContainer title="Mon espace bêta">
          <p className="py-8 text-stone-500">Chargement de votre espace bêta...</p>
        </PageContainer>
      </SiteLayout>
    );
  }

  if (dashboard.isProfileError || !dashboard.profile) {
    return (
      <SiteLayout headerVariant="light">
        <PageContainer title="Espace Bêta-Testeur">
          <BetaEmptyProfileState />
        </PageContainer>
      </SiteLayout>
    );
  }

  return (
    <SiteLayout headerVariant="light">
      <PageContainer title="Mon espace bêta">
        {dashboard.errorMessage && <FeedbackMessage>{dashboard.errorMessage}</FeedbackMessage>}

        <BetaDashboardStats
          campaignsCount={dashboard.campaigns.length}
          openReports={dashboard.openReports}
          resolvedReports={dashboard.resolvedReports}
        />

        <BetaProfileSummary canReport={dashboard.canReport} profileStatus={dashboard.profileStatus} />

        <BetaCampaignsSection
          campaigns={dashboard.campaigns}
          canReport={dashboard.canReport}
          onOpenCampaign={dashboard.setViewedCampaign}
        />

        <BetaCampaignDetailsDialog
          canCreateReport={dashboard.viewedCampaignCanReport}
          campaign={dashboard.viewedCampaign}
          reports={dashboard.viewedCampaignReports}
          onClose={() => dashboard.setViewedCampaign(null)}
          onCreateReport={dashboard.openCampaignReport}
          onOpenReport={(reportId) => {
            dashboard.setViewedCampaign(null);
            dashboard.openReportFollowUp(reportId);
          }}
        />

        {dashboard.effectiveSelectedReportId !== null && dashboard.activeReport && (
          <BetaReportFollowUpDialog
            commentPage={dashboard.commentPage}
            comments={dashboard.comments}
            commentsMeta={dashboard.commentsMeta}
            loadingComments={dashboard.loadingComments}
            newCommentText={dashboard.newCommentText}
            open={dashboard.effectiveSelectedReportId !== null}
            report={dashboard.activeReport}
            sending={dashboard.postCommentPending}
            onClose={dashboard.closeReportFollowUp}
            onCommentPageChange={dashboard.setCommentPage}
            onCommentTextChange={dashboard.setNewCommentText}
            onSubmit={dashboard.handlePostComment}
          />
        )}

        <BetaBugReportDialog
          open={dashboard.isCreateReportOpen}
          onClose={() => {
            dashboard.setIsCreateReportOpen(false);
            dashboard.setSelectedCampaign(null);
          }}
          campaignId={dashboard.selectedCampaign?.id}
          campaignName={dashboard.selectedCampaign?.name}
        />

        <PaginationControls
          page={dashboard.reportPage}
          totalPages={dashboard.reportsMeta?.totalPages ?? 1}
          onPageChange={dashboard.setReportPage}
        />
      </PageContainer>
    </SiteLayout>
  );
};
