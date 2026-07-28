import { Plus } from 'lucide-react';

import { AdminCampaignDetailDialog } from '../components/campaigns/AdminCampaignDetailDialog';
import { AdminCampaignFormDialog } from '../components/campaigns/AdminCampaignFormDialog';
import { AdminCampaignReportMessagesDialog } from '../components/campaigns/AdminCampaignReportMessagesDialog';
import { AdminCampaignsTable } from '../components/campaigns/AdminCampaignsTable';
import {
  ADMIN_CAMPAIGN_REPORTS_PER_PAGE,
  useAdminBetaCampaignsController,
} from '../hooks/useAdminBetaCampaignsController';
import { PageContainer } from '@/shared/components/layout/PageContainer';

export const AdminBetaCampaignsPage = () => {
  const campaigns = useAdminBetaCampaignsController();

  return (
    <PageContainer size="admin" title="Gestion des campagnes bêta">
      <header className="mb-6 flex flex-wrap items-center justify-between gap-4">
        <p className="text-stone-500">Planifiez, lancez et gérez les campagnes de tests de l'application.</p>
        <button
          type="button"
          onClick={() => campaigns.setIsAddOpen(true)}
          className="inline-flex items-center gap-2 rounded-lg bg-brand-700 px-4 py-2 text-sm font-semibold text-white shadow-sm transition hover:bg-brand-800"
        >
          <Plus size={16} />
          <span>Nouvelle campagne</span>
        </button>
      </header>

      {campaigns.isLoading ? (
        <p className="text-stone-500">Chargement des campagnes...</p>
      ) : campaigns.error ? (
        <p className="text-red-600">Erreur lors du chargement des campagnes.</p>
      ) : campaigns.campaigns.length === 0 ? (
        <div className="rounded-lg border border-stone-200 bg-white p-8 text-center text-stone-500">
          Aucune campagne bêta configurée pour le moment.
        </div>
      ) : (
        <AdminCampaignsTable
          campaigns={campaigns.campaigns}
          onDelete={campaigns.handleDelete}
          onEdit={campaigns.openEdit}
          onOpenDetail={campaigns.openDetail}
        />
      )}

      <AdminCampaignFormDialog
        description="Renseignez les détails pour créer une nouvelle campagne de tests."
        form={campaigns.addForm}
        isPending={campaigns.createPending}
        open={campaigns.isAddOpen}
        pendingLabel="Création..."
        submitLabel="Créer la campagne"
        title="Nouvelle campagne bêta"
        onClose={() => campaigns.setIsAddOpen(false)}
        onFormChange={campaigns.setAddForm}
        onSubmit={campaigns.handleAddSubmit}
      />

      <AdminCampaignFormDialog
        description="Modifiez le nom, la description, l’état ou les dates de la campagne."
        form={campaigns.editForm}
        isPending={campaigns.updatePending}
        open={campaigns.isEditOpen && campaigns.selectedCampaign !== null}
        pendingLabel="Enregistrement..."
        submitLabel="Enregistrer"
        title="Modifier la campagne bêta"
        onClose={() => campaigns.setIsEditOpen(false)}
        onFormChange={campaigns.setEditForm}
        onSubmit={campaigns.handleEditSubmit}
      />

      <AdminCampaignDetailDialog
        campaign={campaigns.selectedCampaign}
        open={campaigns.isDetailOpen && campaigns.selectedCampaign !== null}
        reportsPage={campaigns.reportsPage}
        reportsPageCount={campaigns.reportsPageCount}
        reportsPerPage={ADMIN_CAMPAIGN_REPORTS_PER_PAGE}
        visibleReports={campaigns.visibleCampaignReports}
        onClose={() => campaigns.setIsDetailOpen(false)}
        onReportsPageChange={campaigns.setReportsPage}
        onSelectReport={(reportId) => {
          campaigns.setSelectedReportId(reportId);
          campaigns.setCommentsPage(1);
        }}
      />

      <AdminCampaignReportMessagesDialog
        comments={campaigns.comments}
        commentsMeta={campaigns.commentsMeta}
        commentsPage={campaigns.commentsPage}
        loadingComments={campaigns.loadingComments}
        newCommentText={campaigns.newCommentText}
        report={campaigns.selectedReport}
        sending={campaigns.postCommentPending}
        onClose={campaigns.closeReportMessages}
        onCommentTextChange={campaigns.setNewCommentText}
        onCommentsPageChange={campaigns.setCommentsPage}
        onSubmit={campaigns.handlePostComment}
      />
    </PageContainer>
  );
};
