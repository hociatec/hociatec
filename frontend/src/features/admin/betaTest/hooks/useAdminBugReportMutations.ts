import { useMutation, useQueryClient } from '@tanstack/react-query';

import {
  assignAdminBugReport,
  createBugReportComment,
  deleteAdminBugReport,
  markAdminBugReportDuplicate,
  updateAdminBugReportStatus,
} from '../api';
import { useConfirm } from '@/shared/components/ui/confirm';
import { useToast } from '@/shared/components/ui/toast';
import { adminBetaQueryKeys } from '@/shared/lib/queryKeys';

export const useAdminBugReportMutations = ({
  newCommentText,
  selectedReportId,
  onCloseModal,
  onDuplicateReset,
  onCommentReset,
}: {
  newCommentText: string;
  selectedReportId: number | null;
  onCloseModal: () => void;
  onDuplicateReset: () => void;
  onCommentReset: () => void;
}) => {
  const queryClient = useQueryClient();
  const toast = useToast();
  const confirm = useConfirm();

  const refreshReports = () => {
    queryClient.invalidateQueries({ queryKey: adminBetaQueryKeys.bugReports() });
    queryClient.invalidateQueries({ queryKey: adminBetaQueryKeys.bugReportDashboard() });
  };

  const refreshSelectedReport = () => {
    queryClient.invalidateQueries({
      queryKey: adminBetaQueryKeys.bugReportActivity(selectedReportId),
    });
  };

  const updateStatusMutation = useMutation({
    mutationFn: ({ id, status }: { id: number; status: string }) => updateAdminBugReportStatus(id, status),
    onSuccess: () => {
      refreshReports();
      refreshSelectedReport();
      toast.show('État mis à jour.', { variant: 'success' });
    },
    onError: (err) => toast.show(err instanceof Error ? err.message : 'Mise à jour impossible.', { variant: 'error' }),
  });

  const assignMutation = useMutation({
    mutationFn: ({ id, assignedToId }: { id: number; assignedToId?: number | null }) => assignAdminBugReport(id, assignedToId),
    onSuccess: () => {
      refreshReports();
      refreshSelectedReport();
      toast.show('Responsable mis à jour.', { variant: 'success' });
    },
    onError: (err) => toast.show(err instanceof Error ? err.message : 'Assignation impossible.', { variant: 'error' }),
  });

  const duplicateMutation = useMutation({
    mutationFn: ({ id, duplicateOfId: target, reason }: { id: number; duplicateOfId: number; reason?: string }) => markAdminBugReportDuplicate(id, target, reason),
    onSuccess: () => {
      onDuplicateReset();
      refreshReports();
      refreshSelectedReport();
      toast.show('Doublon enregistré.', { variant: 'success' });
    },
    onError: (err) => toast.show(err instanceof Error ? err.message : 'Rattachement impossible.', { variant: 'error' }),
  });

  const deleteMutation = useMutation({
    mutationFn: (id: number) => deleteAdminBugReport(id),
    onSuccess: () => {
      refreshReports();
      onCloseModal();
      toast.show('Signalement supprimé.', { variant: 'success' });
    },
    onError: (err) => toast.show(err instanceof Error ? err.message : 'Suppression impossible.', { variant: 'error' }),
  });

  const postCommentMutation = useMutation({
    mutationFn: () => createBugReportComment(selectedReportId!, newCommentText),
    onSuccess: () => {
      onCommentReset();
      queryClient.invalidateQueries({
        queryKey: adminBetaQueryKeys.bugReportComments(selectedReportId),
      });
      refreshSelectedReport();
      refreshReports();
      toast.show('Message envoyé.', { variant: 'success' });
    },
    onError: (err) => toast.show(err instanceof Error ? err.message : 'Erreur lors de l’envoi du message.', { variant: 'error' }),
  });

  const handleDelete = async (id: number) => {
    if (await confirm({
      title: 'Supprimer le signalement',
      description: 'Cette suppression est définitive.',
      confirmLabel: 'Supprimer',
      cancelLabel: 'Annuler',
    })) {
      deleteMutation.mutate(id);
    }
  };

  return {
    duplicatePending: duplicateMutation.isPending,
    postCommentPending: postCommentMutation.isPending,
    assignReport: (id: number, assignedToId?: number | null) => assignMutation.mutate({ id, assignedToId }),
    deleteReport: handleDelete,
    duplicateReport: (payload: { id: number; duplicateOfId: number; reason?: string }) => duplicateMutation.mutate(payload),
    postComment: () => postCommentMutation.mutate(),
    updateReportStatus: (id: number, status: string) => updateStatusMutation.mutate({ id, status }),
  };
};
