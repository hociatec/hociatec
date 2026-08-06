import { useState, type FormEvent } from 'react';

import { BetaProfileDialog } from '../components/BetaProfileDialog';
import { BetaBugReportDialog } from '../components/BetaBugReportDialog';
import { BetaCampaignDetailsDialog } from '../components/dashboard/BetaCampaignDetailsDialog';
import { BetaCampaignsSection } from '../components/dashboard/BetaCampaignsSection';
import { BetaDashboardStats } from '../components/dashboard/BetaDashboardStats';
import { BetaEmptyProfileState } from '../components/dashboard/BetaEmptyProfileState';
import { BetaProfileSummary } from '../components/dashboard/BetaProfileSummary';
import { BetaReportFollowUpDialog } from '../components/dashboard/BetaReportFollowUpDialog';
import { useBetaDashboardController } from '../hooks/useBetaDashboardController';
import {
  buildBetaProfileForm,
  emptyBetaProfileForm,
  isBetaProfileComplete,
  type EditableProfile,
} from '../lib/betaProfileForm';
import { PageContainer } from '@/shared/components/layout/PageContainer';
import { SiteLayout } from '@/shared/components/layout/SiteLayout';
import { PaginationControls } from '@/shared/components/ui/PaginationControls';
import { useConfirm } from '@/shared/components/ui/confirm';
import { FeedbackMessage } from '@/shared/components/ui/page-state';

export const BetaDashboardPage = () => {
  const dashboard = useBetaDashboardController();
  const confirm = useConfirm();
  const [profileDialogMode, setProfileDialogMode] = useState<'create' | 'edit' | null>(null);
  const [profileForm, setProfileForm] = useState<EditableProfile | null>(null);
  const [profileFormError, setProfileFormError] = useState<string | null>(null);

  const openProfileDialog = (mode: 'create' | 'edit') => {
    setProfileForm(mode === 'edit' && dashboard.profile ? buildBetaProfileForm(dashboard.profile) : emptyBetaProfileForm());
    setProfileFormError(null);
    setProfileDialogMode(mode);
  };

  const closeProfileDialog = () => {
    if (dashboard.updatingProfile || dashboard.isDeletingProfile) return;
    setProfileDialogMode(null);
    setProfileForm(null);
    setProfileFormError(null);
  };

  const submitProfile = async (event: FormEvent<HTMLFormElement>) => {
    event.preventDefault();
    if (!profileForm) return;

    if (!isBetaProfileComplete(profileForm)) {
      setProfileFormError('Veuillez remplir tous les choix obligatoires (*) du profil bêta.');
      return;
    }

    try {
      await dashboard.updateProfile(profileForm);
      closeProfileDialog();
    } catch (error) {
      setProfileFormError(
        error instanceof Error ? error.message : 'Impossible d’enregistrer le profil bêta.',
      );
    }
  };

  const deleteProfile = async () => {
    const confirmed = await confirm({
      title: 'Supprimer le profil bêta',
      description: 'Supprimer définitivement votre profil bêta ?',
      confirmLabel: 'Supprimer',
      cancelLabel: 'Annuler',
    });
    if (!confirmed) return;

    try {
      await dashboard.deleteProfile();
      closeProfileDialog();
    } catch (error) {
      setProfileFormError(
        error instanceof Error ? error.message : 'Impossible de supprimer le profil bêta.',
      );
    }
  };

  if (dashboard.isLoadingProfile) {
    return (
      <SiteLayout headerVariant="light">
        <PageContainer title="Mon espace bêta">
          <p className="sr-only">Chargement de votre espace bêta...</p>
        </PageContainer>
      </SiteLayout>
    );
  }

  if (dashboard.isProfileError || !dashboard.profile) {
    return (
      <SiteLayout headerVariant="light">
        <PageContainer title="Espace Bêta-Testeur">
          <BetaEmptyProfileState onCreate={() => openProfileDialog('create')} />
          {profileDialogMode && profileForm && dashboard.choices ? (
            <BetaProfileDialog
              choices={dashboard.choices}
              error={profileFormError ?? dashboard.profileErrorMessage}
              form={profileForm}
              mode={profileDialogMode}
              saving={dashboard.updatingProfile}
              deleting={dashboard.isDeletingProfile}
              onClose={closeProfileDialog}
              onSubmit={submitProfile}
              setForm={setProfileForm}
            />
          ) : null}
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

        <BetaProfileSummary
          canReport={dashboard.canReport}
          onEdit={() => openProfileDialog('edit')}
          profileStatus={dashboard.profileStatus}
        />

        {profileDialogMode && profileForm && dashboard.choices ? (
          <BetaProfileDialog
            choices={dashboard.choices}
            error={profileFormError ?? dashboard.profileErrorMessage}
            form={profileForm}
            mode={profileDialogMode}
            saving={dashboard.updatingProfile}
            deleting={dashboard.isDeletingProfile}
            onClose={closeProfileDialog}
            onDelete={profileDialogMode === 'edit' ? deleteProfile : undefined}
            onSubmit={submitProfile}
            setForm={setProfileForm}
          />
        ) : null}

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
          {...(dashboard.selectedCampaign
            ? {
                campaignId: dashboard.selectedCampaign.id,
                campaignName: dashboard.selectedCampaign.name,
              }
            : {})}
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
