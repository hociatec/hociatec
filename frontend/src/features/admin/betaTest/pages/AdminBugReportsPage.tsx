import { useState } from 'react';
import { useSearchParams } from 'react-router';
import { useMutation, useQuery, useQueryClient } from '@tanstack/react-query';
import { Download } from 'lucide-react';

import {
  assignAdminBugReport,
  createBugReportComment,
  deleteAdminBugReport,
  exportAdminBugReports,
  fetchAdminBugReport,
  fetchAdminBugReportDashboard,
  fetchAdminBugReports,
  fetchBugReportActivity,
  fetchBugReportComments,
  markAdminBugReportDuplicate,
  updateAdminBugReportStatus,
  type AdminBugReportDto,
} from '../api';
import { AdminBugReportDashboardStats } from '../components/reports/AdminBugReportDashboardStats';
import { AdminBugReportDetailDialog } from '../components/reports/AdminBugReportDetailDialog';
import { AdminBugReportFilters } from '../components/reports/AdminBugReportFilters';
import { AdminBugReportsTable } from '../components/reports/AdminBugReportsTable';
import { useAuth } from '@/features/auth/hooks/useAuth';
import { useConfirm } from '@/shared/components/ui/confirm';
import { PaginationControls } from '@/shared/components/ui/PaginationControls';
import { useToast } from '@/shared/components/ui/toast';

export const AdminBugReportsPage = () => {
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
    queryKey: ['adminBugReportDashboard'],
    queryFn: fetchAdminBugReportDashboard,
  });

  const { data: reportsResult, isLoading, error } = useQuery({
    queryKey: ['adminBugReports', filters],
    queryFn: () => fetchAdminBugReports(filters),
  });

  const reports = reportsResult?.items ?? [];
  const meta = reportsResult?.meta ?? null;

  const { data: selectedReport } = useQuery({
    queryKey: ['adminBugReport', selectedReportId],
    queryFn: () => fetchAdminBugReport(selectedReportId!),
    enabled: selectedReportId !== null,
  });
  const activeReport = reports.find((report) => report.id === selectedReportId) ?? selectedReport;

  const { data: commentsResult, isLoading: loadingComments } = useQuery({
    queryKey: ['bugReportComments', selectedReportId, commentPage],
    queryFn: () => fetchBugReportComments(selectedReportId!, commentPage),
    enabled: selectedReportId !== null,
  });
  const comments = commentsResult?.items ?? [];
  const commentsMeta = commentsResult?.meta ?? null;

  const { data: activities = [] } = useQuery({
    queryKey: ['bugReportActivity', selectedReportId],
    queryFn: () => fetchBugReportActivity(selectedReportId!),
    enabled: selectedReportId !== null,
  });

  const refreshReports = () => {
    queryClient.invalidateQueries({ queryKey: ['adminBugReports'] });
    queryClient.invalidateQueries({ queryKey: ['adminBugReportDashboard'] });
  };

  const updateStatusMutation = useMutation({
    mutationFn: ({ id, status }: { id: number; status: string }) => updateAdminBugReportStatus(id, status),
    onSuccess: () => {
      refreshReports();
      queryClient.invalidateQueries({ queryKey: ['bugReportActivity', selectedReportId] });
      toast.show('État mis à jour.', { variant: 'success' });
    },
    onError: (err) => toast.show(err instanceof Error ? err.message : 'Mise à jour impossible.', { variant: 'error' }),
  });

  const assignMutation = useMutation({
    mutationFn: ({ id, assignedToId }: { id: number; assignedToId?: number | null }) => assignAdminBugReport(id, assignedToId),
    onSuccess: () => {
      refreshReports();
      queryClient.invalidateQueries({ queryKey: ['bugReportActivity', selectedReportId] });
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
      queryClient.invalidateQueries({ queryKey: ['bugReportActivity', selectedReportId] });
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
      queryClient.invalidateQueries({ queryKey: ['bugReportComments', selectedReportId] });
      queryClient.invalidateQueries({ queryKey: ['bugReportActivity', selectedReportId] });
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

  return (
    <div className="admin-page">
      <header className="mb-6 flex flex-wrap items-center justify-between gap-4">
        <div>
          <h1 className="text-2xl font-bold">Gestion des signalements</h1>
          <p className="text-stone-500">Traitement, priorisation, échanges et journal technique du programme bêta.</p>
        </div>
        <button
          type="button"
          onClick={() => exportAdminBugReports(filters)}
          className="inline-flex items-center gap-2 rounded-lg border border-stone-200 bg-white px-4 py-3 text-sm font-semibold text-stone-700 hover:bg-stone-50"
        >
          <Download size={16} />
          Export CSV
        </button>
      </header>

      <AdminBugReportDashboardStats dashboard={dashboard} />

      <AdminBugReportFilters
        assignedFilter={assignedFilter}
        dashboard={dashboard}
        search={search}
        severityFilter={severityFilter}
        statusFilter={statusFilter}
        onAssignedFilterChange={(value) => {
          setAssignedFilter(value);
          setPage(1);
        }}
        onReset={resetFilters}
        onSearchChange={(value) => {
          setSearch(value);
          setPage(1);
        }}
        onSeverityFilterChange={(value) => {
          setSeverityFilter(value);
          setPage(1);
        }}
        onStatusFilterChange={(value) => {
          setStatusFilter(value);
          setPage(1);
        }}
        onUseMyReports={useMyReports}
      />

      {isLoading ? (
        <p className="text-stone-500">Chargement des signalements...</p>
      ) : error ? (
        <p className="text-red-600">Erreur lors du chargement des signalements.</p>
      ) : reports.length === 0 ? (
        <p className="rounded-lg border border-stone-200 bg-white p-8 text-center text-stone-500">Aucun signalement ne correspond à ces critères.</p>
      ) : (
        <AdminBugReportsTable
          dashboard={dashboard}
          reports={reports}
          onAssign={(id, assignedToId) => assignMutation.mutate({ id, assignedToId })}
          onDelete={handleDelete}
          onOpen={openModal}
          onStatusChange={(id, status) => updateStatusMutation.mutate({ id, status })}
        />
      )}

      <PaginationControls
        page={page}
        total={meta?.total}
        totalLabel="signalement"
        totalPages={meta?.totalPages ?? 1}
        onPageChange={setPage}
      />

      <AdminBugReportDetailDialog
        activities={activities}
        commentPage={commentPage}
        comments={comments}
        commentsMeta={commentsMeta}
        duplicateOfId={duplicateOfId}
        duplicatePending={duplicateMutation.isPending}
        duplicateReason={duplicateReason}
        loadingComments={loadingComments}
        newCommentText={newCommentText}
        postCommentPending={postCommentMutation.isPending}
        report={activeReport}
        onClose={closeModal}
        onCommentPageChange={setCommentPage}
        onDuplicateIdChange={setDuplicateOfId}
        onDuplicateReasonChange={setDuplicateReason}
        onDuplicateSubmit={(payload) => duplicateMutation.mutate(payload)}
        onNewCommentTextChange={setNewCommentText}
        onPostComment={() => postCommentMutation.mutate()}
      />
    </div>
  );
};
