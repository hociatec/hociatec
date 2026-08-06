import { type FormEvent } from 'react';
import { useMutation, useQueryClient } from '@tanstack/react-query';

import {
  createAdminCampaign,
  createBugReportComment,
  deleteAdminCampaign,
  updateAdminCampaign,
  type AdminCampaignDto,
} from '../api';
import { emptyCampaignForm, type CampaignFormState } from '../lib/campaignForms';
import { useConfirm } from '@/shared/components/ui/confirm';
import { useToast } from '@/shared/components/ui/toast';
import { adminBetaQueryKeys } from '@/features/betaTest/publicApi';
import { notifyMutationError } from '@/shared/lib/notificationConventions';

export const useAdminCampaignMutations = ({
  addForm,
  editForm,
  newCommentText,
  selectedCampaign,
  selectedReportId,
  onAddClose,
  onEditClose,
  onCommentReset,
}: {
  addForm: CampaignFormState;
  editForm: CampaignFormState;
  newCommentText: string;
  selectedCampaign: AdminCampaignDto | null;
  selectedReportId: number | null;
  onAddClose: (form: CampaignFormState) => void;
  onEditClose: () => void;
  onCommentReset: () => void;
}) => {
  const queryClient = useQueryClient();
  const toast = useToast();
  const confirm = useConfirm();

  const refreshCampaigns = () => {
    queryClient.invalidateQueries({ queryKey: adminBetaQueryKeys.campaigns() });
  };

  const createMutation = useMutation({
    mutationFn: (payload: CampaignFormState) => createAdminCampaign(payload),
    onSuccess: () => {
      refreshCampaigns();
      toast.show('La campagne bêta a été créée avec succès.', { variant: 'success' });
      onAddClose(emptyCampaignForm());
    },
    onError: (err) => notifyMutationError(toast, err, 'Erreur lors de la création.'),
  });

  const updateMutation = useMutation({
    mutationFn: ({ id, payload }: { id: number; payload: CampaignFormState }) =>
      updateAdminCampaign(id, payload),
    onSuccess: () => {
      refreshCampaigns();
      toast.show('La campagne bêta a été mise à jour.', { variant: 'success' });
      onEditClose();
    },
    onError: (err) => notifyMutationError(toast, err, 'Erreur lors de la modification.'),
  });

  const deleteMutation = useMutation({
    mutationFn: (id: number) => deleteAdminCampaign(id),
    onSuccess: () => {
      refreshCampaigns();
      toast.show('La campagne bêta a été supprimée.', { variant: 'success' });
    },
    onError: (err) => notifyMutationError(toast, err, 'Erreur lors de la suppression.'),
  });

  const postCommentMutation = useMutation({
    mutationFn: () => createBugReportComment(selectedReportId!, newCommentText),
    onSuccess: () => {
      onCommentReset();
      queryClient.invalidateQueries({
        queryKey: adminBetaQueryKeys.bugReportComments(selectedReportId),
      });
      toast.show('Message envoyé.', { variant: 'success' });
    },
    onError: (err) => notifyMutationError(toast, err, "Erreur lors de l'envoi du message."),
  });

  const handleAddSubmit = (event: FormEvent) => {
    event.preventDefault();
    if (!addForm.name.trim() || !addForm.description.trim()) {
      toast.show('Le nom et la description sont obligatoires.', { variant: 'error' });
      return;
    }
    createMutation.mutate(addForm);
  };

  const handleEditSubmit = (event: FormEvent) => {
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

  const handlePostComment = (event: FormEvent) => {
    event.preventDefault();
    if (!newCommentText.trim()) return;
    postCommentMutation.mutate();
  };

  return {
    createPending: createMutation.isPending,
    postCommentPending: postCommentMutation.isPending,
    updatePending: updateMutation.isPending,
    handleAddSubmit,
    handleDelete,
    handleEditSubmit,
    handlePostComment,
  };
};
