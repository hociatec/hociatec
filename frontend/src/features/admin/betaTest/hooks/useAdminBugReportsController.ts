import { useState } from 'react';
import { useSearchParams } from 'react-router';
import { useMutation, useQuery, useQueryClient } from '@tanstack/react-query';

import {
  assignAdminBugReport,
  createBugReportComment,
  deleteAdminBugReport,
  fetchAdminBugReport,
  fetchAdminBugReportDashboard,
  fetchAdminBugReports,
  fetchBugReportActivity,
  fetchBugReportComments,
  markAdminBugReportDuplicate,
  updateAdminBugReportStatus,
  type AdminBugReportDto,
} from '../api';
import { useAuth } from '@/features/auth/hooks/useAuth';
import { useConfirm } from '@/shared/components/ui/confirm';
import { useToast } from '@/shared/components/ui/toast';
import { adminBetaQueryKeys } from '@/shared/lib/queryKeys';

export const useAdminBugReportsController = () => {
  const queryClient = useQueryClient();
  const toast = useToast();
  const confirm = useConfirm();
  const { user } = useAuth();
  const [searchParams, setSearchParams] = useSearchParams();
  const [page, setPage] = useState(1);
  const [commentPage, setCommentPage] = useState(1);
  const [statusFilter, setStatusFilter] = useState('');
  const [severityFilter, setSeverityFilter] = useState('');
  const [search, setSearch] = useState('');
  const [assignedFilter, setAssignedFilter] = useState('');
  const [selectedReportId, setSelectedReportId] = useState<number | null>(Number(searchParams.get('reportId') ?? 0) || null);
  const [newCommentText, setNewCommentText] = useState('');
  const [duplicateOfId, setDuplicateOfId] = useState('');
  const [duplicateReason, setDuplicateReason] = useState('');

  const filters = {
    page,
    perPage: 12,
    status: statusFilter || undefined,
    severity: severityFilter || undefined,
    search: search.trim() || undefined,
    assignedTo: assignedFilter || undefined,
  };

  const { data: dashboard } = useQuery({
    queryKey: adminBetaQueryKeys.bugReportDashboard(),
    queryFn: fetchAdminBugReportDashboard,
  });

  const { data: reportsResult, isLoading, error } = useQuery({
    queryKey: adminBetaQueryKeys.bugReportsList(filters),
    queryFn: () => fetchAdminBugReports(filters),
  });

  const reports = reportsResult?.items ?? [];
  const meta = reportsResult?.meta ?? null;

  const { data: selectedReport } = useQuery({
    queryKey: adminBetaQueryKeys.bugReport(selectedReportId),
    queryFn: () => fetchAdminBugReport(selectedReportId!),
    enabled: selectedReportId !== null,
  });
  const activeReport = reports.find((report) => report.id === selectedReportId) ?? selectedReport;

  const { data: commentsResult, isLoading: loadingComments } = useQuery({
    queryKey: adminBetaQueryKeys.bugReportCommentsPage(selectedReportId, commentPage),
    queryFn: () => fetchBugReportComments(selectedReportId!, commentPage),
    enabled: selectedReportId !== null,
  });

  const { data: activities = [] } = useQuery({
    queryKey: adminBetaQueryKeys.bugReportActivity(selectedReportId),
    queryFn: () => fetchBugReportActivity(selectedReportId!),
    enabled: selectedReportId !== null,
  });

  const refreshReports = () => {
    queryClient.invalidateQueries({ queryKey: adminBetaQueryKeys.bugReports() });
    queryClient.invalidateQueries({ queryKey: adminBetaQueryKeys.bugReportDashboard() });
  };

  const updateStatusMutation = useMutation({
    mutationFn: ({ id, status }: { id: number; status: string }) => updateAdminBugReportStatus(id, status),
    onSuccess: () => {
      refreshReports();
      queryClient.invalidateQueries({
        queryKey: adminBetaQueryKeys.bugReportActivity(selectedReportId),
      });
      toast.show('État mis à jour.', { variant: 'success' });
    },
    onError: (err) => toast.show(err instanceof Error ? err.message : 'Mise à jour impossible.', { variant: 'error' }),
  });

  const assignMutation = useMutation({
    mutationFn: ({ id, assignedToId }: { id: number; assignedToId?: number | null }) => assignAdminBugReport(id, assignedToId),
    onSuccess: () => {
      refreshReports();
      queryClient.invalidateQueries({
        queryKey: adminBetaQueryKeys.bugReportActivity(selectedReportId),
      });
      toast.show('Responsable mis à jour.', { variant: 'success' });
    },
    onError: (err) => toast.show(err instanceof Error ? err.message : 'Assignation impossible.', { variant: 'error' }),
  });

  const duplicateMutation = useMutation({
    mutationFn: ({ id, duplicateOfId: target, reason }: { id: number; duplicateOfId: number; reason?: string }) => markAdminBugReportDuplicate(id, target, reason),
    onSuccess: () => {
      setDuplicateOfId('');
      setDuplicateReason('');
      refreshReports();
      queryClient.invalidateQueries({
        queryKey: adminBetaQueryKeys.bugReportActivity(selectedReportId),
      });
      toast.show('Doublon enregistré.', { variant: 'success' });
    },
    onError: (err) => toast.show(err instanceof Error ? err.message : 'Rattachement impossible.', { variant: 'error' }),
  });

  const deleteMutation = useMutation({
    mutationFn: (id: number) => deleteAdminBugReport(id),
    onSuccess: () => {
      refreshReports();
      closeModal();
      toast.show('Signalement supprimé.', { variant: 'success' });
    },
    onError: (err) => toast.show(err instanceof Error ? err.message : 'Suppression impossible.', { variant: 'error' }),
  });

  const postCommentMutation = useMutation({
    mutationFn: () => createBugReportComment(selectedReportId!, newCommentText),
    onSuccess: () => {
      setNewCommentText('');
      queryClient.invalidateQueries({
        queryKey: adminBetaQueryKeys.bugReportComments(selectedReportId),
      });
      queryClient.invalidateQueries({
        queryKey: adminBetaQueryKeys.bugReportActivity(selectedReportId),
      });
      refreshReports();
      toast.show('Message envoyé.', { variant: 'success' });
    },
    onError: (err) => toast.show(err instanceof Error ? err.message : 'Erreur lors de l’envoi du message.', { variant: 'error' }),
  });

  const openModal = (report: AdminBugReportDto) => {
    setSelectedReportId(report.id);
    setCommentPage(1);
    setSearchParams({ reportId: String(report.id) });
  };

  const closeModal = () => {
    setSelectedReportId(null);
    setCommentPage(1);
    setSearchParams({});
  };

  const resetFilters = () => {
    setPage(1);
    setStatusFilter('');
    setSeverityFilter('');
    setAssignedFilter('');
    setSearch('');
  };

  const useMyReports = () => {
    if (!user?.id) return;
    setAssignedFilter(String(user.id));
    setPage(1);
  };

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
    activeReport,
    activities,
    assignedFilter,
    comments: commentsResult?.items ?? [],
    commentsMeta: commentsResult?.meta ?? null,
    commentPage,
    dashboard,
    duplicateOfId,
    duplicatePending: duplicateMutation.isPending,
    duplicateReason,
    error,
    filters,
    isLoading,
    loadingComments,
    meta,
    newCommentText,
    page,
    postCommentPending: postCommentMutation.isPending,
    reports,
    search,
    severityFilter,
    statusFilter,
    assignReport: (id: number, assignedToId?: number | null) => assignMutation.mutate({ id, assignedToId }),
    closeModal,
    deleteReport: handleDelete,
    duplicateReport: (payload: { id: number; duplicateOfId: number; reason?: string }) => duplicateMutation.mutate(payload),
    openModal,
    postComment: () => postCommentMutation.mutate(),
    resetFilters,
    setAssignedFilter,
    setCommentPage,
    setDuplicateOfId,
    setDuplicateReason,
    setNewCommentText,
    setPage,
    setSearch,
    setSeverityFilter,
    setStatusFilter,
    updateReportStatus: (id: number, status: string) => updateStatusMutation.mutate({ id, status }),
    useMyReports,
  };
};
