import { useEffect, useState } from 'react';
import { useMutation, useQuery, useQueryClient } from '@tanstack/react-query';

import {
  createAdminCampaign,
  createBugReportComment,
  deleteAdminCampaign,
  fetchAdminCampaigns,
  fetchBugReportComments,
  updateAdminCampaign,
  type AdminCampaignDto,
} from '../api';
import { emptyCampaignForm, type CampaignFormState } from '../lib/campaignForms';
import { useConfirm } from '@/shared/components/ui/confirm';
import { useToast } from '@/shared/components/ui/toast';
import { formatApiDateForDateInput } from '@/shared/lib/formatters';
import { adminBetaQueryKeys } from '@/shared/lib/queryKeys';

const REPORTS_PER_PAGE = 6;

export const useAdminBetaCampaignsController = () => {
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
    queryKey: adminBetaQueryKeys.campaigns(),
    queryFn: fetchAdminCampaigns,
  });

  const { data: commentsResult, isLoading: loadingComments } = useQuery({
    queryKey: adminBetaQueryKeys.bugReportCommentsPage(selectedReportId, commentsPage),
    queryFn: () => fetchBugReportComments(selectedReportId!, commentsPage),
    enabled: selectedReportId !== null,
  });

  const createMutation = useMutation({
    mutationFn: (payload: CampaignFormState) => createAdminCampaign(payload),
    onSuccess: () => {
      queryClient.invalidateQueries({ queryKey: adminBetaQueryKeys.campaigns() });
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
      queryClient.invalidateQueries({ queryKey: adminBetaQueryKeys.campaigns() });
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
      queryClient.invalidateQueries({ queryKey: adminBetaQueryKeys.campaigns() });
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
      queryClient.invalidateQueries({
        queryKey: adminBetaQueryKeys.bugReportComments(selectedReportId),
      });
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
      startsAt: formatApiDateForDateInput(campaign.startsAt),
      endsAt: formatApiDateForDateInput(campaign.endsAt),
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

  return {
    addForm,
    campaigns,
    comments: commentsResult?.items ?? [],
    commentsMeta: commentsResult?.meta ?? null,
    commentsPage,
    createPending: createMutation.isPending,
    editForm,
    error,
    isAddOpen,
    isDetailOpen,
    isEditOpen,
    isLoading,
    loadingComments,
    newCommentText,
    postCommentPending: postCommentMutation.isPending,
    reportsPage,
    reportsPageCount,
    selectedCampaign,
    selectedReport,
    updatePending: updateMutation.isPending,
    visibleCampaignReports,
    closeReportMessages,
    handleAddSubmit,
    handleDelete,
    handleEditSubmit,
    handlePostComment,
    openDetail,
    openEdit,
    setAddForm,
    setCommentsPage,
    setEditForm,
    setIsAddOpen,
    setIsDetailOpen,
    setIsEditOpen,
    setNewCommentText,
    setReportsPage,
    setSelectedReportId,
  };
};

export { REPORTS_PER_PAGE as ADMIN_CAMPAIGN_REPORTS_PER_PAGE };
