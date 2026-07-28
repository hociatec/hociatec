import { useEffect, useState } from 'react';
import { useMutation, useQuery, useQueryClient } from '@tanstack/react-query';
import { Plus } from 'lucide-react';

import {
  createAdminCampaign,
  createBugReportComment,
  deleteAdminCampaign,
  fetchAdminCampaigns,
  fetchBugReportComments,
  updateAdminCampaign,
  type AdminCampaignDto,
} from '../api';
import { AdminCampaignDetailDialog } from '../components/campaigns/AdminCampaignDetailDialog';
import { AdminCampaignFormDialog } from '../components/campaigns/AdminCampaignFormDialog';
import { AdminCampaignReportMessagesDialog } from '../components/campaigns/AdminCampaignReportMessagesDialog';
import { AdminCampaignsTable } from '../components/campaigns/AdminCampaignsTable';
import { emptyCampaignForm, type CampaignFormState } from '../lib/campaignForms';
import { PageContainer } from '@/shared/components/layout/PageContainer';
import { useConfirm } from '@/shared/components/ui/confirm';
import { useToast } from '@/shared/components/ui/toast';

const REPORTS_PER_PAGE = 6;

export const AdminBetaCampaignsPage = () => {
  const queryClient = useQueryClient();
  const toast = useToast();
  const confirm = useConfirm();

  const [isAddOpen, setIsAddOpen] = useState(false);
  const [isEditOpen, setIsEditOpen] = useState(false);
  const [isDetailOpen, setIsDetailOpen] = useState(false);
  const [selectedCampaign, setSelectedCampaign] = useState<AdminCampaignDto | null>(null);
  const [selectedReportId, setSelectedReportId] = useState<number | null>(null);
  const [newCommentText, setNewCommentText] = useState('');
  const [reportsPage, setReportsPage] = useState(1);
  const [commentsPage, setCommentsPage] = useState(1);
  const [addForm, setAddForm] = useState(emptyCampaignForm);
  const [editForm, setEditForm] = useState<CampaignFormState>({
    name: '',
    description: '',
    status: 'draft',
    startsAt: '',
    endsAt: '',
  });

  const { data: campaigns = [], isLoading, error } = useQuery({
    queryKey: ['adminCampaigns'],
    queryFn: fetchAdminCampaigns,
  });

  const { data: commentsResult, isLoading: loadingComments } = useQuery({
    queryKey: ['bugReportComments', selectedReportId, commentsPage],
    queryFn: () => fetchBugReportComments(selectedReportId!, commentsPage),
    enabled: selectedReportId !== null,
  });

  const createMutation = useMutation({
    mutationFn: (payload: CampaignFormState) => createAdminCampaign(payload),
    onSuccess: () => {
      queryClient.invalidateQueries({ queryKey: ['adminCampaigns'] });
      toast.show('La campagne bêta a été créée avec succès.', { variant: 'success' });
      setIsAddOpen(false);
      setAddForm(emptyCampaignForm());
    },
    onError: (err) => {
      toast.show(err instanceof Error ? err.message : 'Erreur lors de la création.', { variant: 'error' });
    },
  });

  const updateMutation = useMutation({
    mutationFn: ({ id, payload }: { id: number; payload: CampaignFormState }) =>
      updateAdminCampaign(id, payload),
    onSuccess: () => {
      queryClient.invalidateQueries({ queryKey: ['adminCampaigns'] });
      toast.show('La campagne bêta a été mise à jour.', { variant: 'success' });
      setIsEditOpen(false);
      setSelectedCampaign(null);
    },
    onError: (err) => {
      toast.show(err instanceof Error ? err.message : 'Erreur lors de la modification.', { variant: 'error' });
    },
  });

  const deleteMutation = useMutation({
    mutationFn: (id: number) => deleteAdminCampaign(id),
    onSuccess: () => {
      queryClient.invalidateQueries({ queryKey: ['adminCampaigns'] });
      toast.show('La campagne bêta a été supprimée.', { variant: 'success' });
    },
    onError: (err) => {
      toast.show(err instanceof Error ? err.message : 'Erreur lors de la suppression.', { variant: 'error' });
    },
  });

  const postCommentMutation = useMutation({
    mutationFn: () => createBugReportComment(selectedReportId!, newCommentText),
    onSuccess: () => {
      setNewCommentText('');
      queryClient.invalidateQueries({ queryKey: ['bugReportComments', selectedReportId] });
      toast.show('Message envoyé.', { variant: 'success' });
    },
    onError: (err) => {
      toast.show(err instanceof Error ? err.message : "Erreur lors de l'envoi du message.", { variant: 'error' });
    },
  });

  const handleAddSubmit = (event: React.FormEvent) => {
    event.preventDefault();
    if (!addForm.name.trim() || !addForm.description.trim()) {
      toast.show('Le nom et la description sont obligatoires.', { variant: 'error' });
      return;
    }
    createMutation.mutate(addForm);
  };

  const handleEditSubmit = (event: React.FormEvent) => {
    event.preventDefault();
    if (!selectedCampaign) return;
    if (!editForm.name.trim() || !editForm.description.trim()) {
      toast.show('Le nom et la description sont obligatoires.', { variant: 'error' });
      return;
    }
    updateMutation.mutate({ id: selectedCampaign.id, payload: editForm });
  };

  const handleDelete = async (campaign: AdminCampaignDto) => {
    if (
      await confirm({
        title: 'Supprimer la campagne',
        description: `Êtes-vous sûr de vouloir supprimer définitivement la campagne "${campaign.name}" ?`,
        confirmLabel: 'Supprimer',
        cancelLabel: 'Annuler',
      })
    ) {
      deleteMutation.mutate(campaign.id);
    }
  };

  const openEdit = (campaign: AdminCampaignDto) => {
    setSelectedCampaign(campaign);
    setEditForm({
      name: campaign.name,
      description: campaign.description,
      status: campaign.status,
      startsAt: campaign.startsAt?.slice(0, 10) ?? '',
      endsAt: campaign.endsAt?.slice(0, 10) ?? '',
    });
    setIsEditOpen(true);
  };

  const openDetail = (campaign: AdminCampaignDto) => {
    setSelectedCampaign(campaign);
    setReportsPage(1);
    setCommentsPage(1);
    setSelectedReportId(null);
    setIsDetailOpen(true);
  };

  const closeReportMessages = () => {
    setSelectedReportId(null);
    setCommentsPage(1);
  };

  const handlePostComment = (event: React.FormEvent) => {
    event.preventDefault();
    if (!newCommentText.trim()) return;
    postCommentMutation.mutate();
  };

  const selectedCampaignReports = selectedCampaign?.reports ?? [];
  const reportsPageCount = Math.max(1, Math.ceil(selectedCampaignReports.length / REPORTS_PER_PAGE));
  const visibleCampaignReports = selectedCampaignReports.slice(
    (reportsPage - 1) * REPORTS_PER_PAGE,
    reportsPage * REPORTS_PER_PAGE,
  );
  const selectedReport = selectedCampaignReports.find((report) => report.id === selectedReportId);
  const comments = commentsResult?.items ?? [];
  const commentsMeta = commentsResult?.meta ?? null;

  useEffect(() => {
    if (!isDetailOpen) return;

    const preventEscape = (event: KeyboardEvent) => {
      if (event.key === 'Escape') {
        event.preventDefault();
        event.stopPropagation();
      }
    };

    document.addEventListener('keydown', preventEscape, true);

    return () => document.removeEventListener('keydown', preventEscape, true);
  }, [isDetailOpen]);

  return (
    <PageContainer size="admin" title="Gestion des campagnes bêta">
      <header className="mb-6 flex flex-wrap items-center justify-between gap-4">
        <p className="text-stone-500">Planifiez, lancez et gérez les campagnes de tests de l'application.</p>
        <button
          type="button"
          onClick={() => setIsAddOpen(true)}
          className="inline-flex items-center gap-2 rounded-lg bg-brand-700 px-4 py-2 text-sm font-semibold text-white shadow-sm transition hover:bg-brand-800"
        >
          <Plus size={16} />
          <span>Nouvelle campagne</span>
        </button>
      </header>

      {isLoading ? (
        <p className="text-stone-500">Chargement des campagnes...</p>
      ) : error ? (
        <p className="text-red-600">Erreur lors du chargement des campagnes.</p>
      ) : campaigns.length === 0 ? (
        <div className="rounded-lg border border-stone-200 bg-white p-8 text-center text-stone-500">
          Aucune campagne bêta configurée pour le moment.
        </div>
      ) : (
        <AdminCampaignsTable
          campaigns={campaigns}
          onDelete={handleDelete}
          onEdit={openEdit}
          onOpenDetail={openDetail}
        />
      )}

      <AdminCampaignFormDialog
        description="Renseignez les détails pour créer une nouvelle campagne de tests."
        form={addForm}
        isPending={createMutation.isPending}
        open={isAddOpen}
        pendingLabel="Création..."
        submitLabel="Créer la campagne"
        title="Nouvelle campagne bêta"
        onClose={() => setIsAddOpen(false)}
        onFormChange={setAddForm}
        onSubmit={handleAddSubmit}
      />

      <AdminCampaignFormDialog
        description="Modifiez le nom, la description, l’état ou les dates de la campagne."
        form={editForm}
        isPending={updateMutation.isPending}
        open={isEditOpen && selectedCampaign !== null}
        pendingLabel="Enregistrement..."
        submitLabel="Enregistrer"
        title="Modifier la campagne bêta"
        onClose={() => setIsEditOpen(false)}
        onFormChange={setEditForm}
        onSubmit={handleEditSubmit}
      />

      <AdminCampaignDetailDialog
        campaign={selectedCampaign}
        open={isDetailOpen && selectedCampaign !== null}
        reportsPage={reportsPage}
        reportsPageCount={reportsPageCount}
        reportsPerPage={REPORTS_PER_PAGE}
        visibleReports={visibleCampaignReports}
        onClose={() => setIsDetailOpen(false)}
        onReportsPageChange={setReportsPage}
        onSelectReport={(reportId) => {
          setSelectedReportId(reportId);
          setCommentsPage(1);
        }}
      />

      <AdminCampaignReportMessagesDialog
        comments={comments}
        commentsMeta={commentsMeta}
        commentsPage={commentsPage}
        loadingComments={loadingComments}
        newCommentText={newCommentText}
        report={selectedReport}
        sending={postCommentMutation.isPending}
        onClose={closeReportMessages}
        onCommentTextChange={setNewCommentText}
        onCommentsPageChange={setCommentsPage}
        onSubmit={handlePostComment}
      />
    </PageContainer>
  );
};
