import { Download } from 'lucide-react';

import { exportAdminBugReports } from '../api';
import { AdminBugReportDashboardStats } from '../components/reports/AdminBugReportDashboardStats';
import { AdminBugReportDetailDialog } from '../components/reports/AdminBugReportDetailDialog';
import { AdminBugReportFilters } from '../components/reports/AdminBugReportFilters';
import { AdminBugReportsTable } from '../components/reports/AdminBugReportsTable';
import { useAdminBugReportsController } from '../hooks/useAdminBugReportsController';
import { PaginationControls } from '@/shared/components/ui/PaginationControls';

export const AdminBugReportsPage = () => {
  const reports = useAdminBugReportsController();

  return (
    <div className="admin-page">
      <header className="mb-6 flex flex-wrap items-center justify-between gap-4">
        <div>
          <h1 className="text-2xl font-bold">Gestion des signalements</h1>
          <p className="text-stone-500">Traitement, priorisation, échanges et journal technique du programme bêta.</p>
        </div>
        <button
          type="button"
          onClick={() => exportAdminBugReports(reports.filters)}
          className="inline-flex items-center gap-2 rounded-lg border border-stone-200 bg-white px-4 py-3 text-sm font-semibold text-stone-700 hover:bg-stone-50"
        >
          <Download size={16} />
          Export CSV
        </button>
      </header>

      <AdminBugReportDashboardStats dashboard={reports.dashboard} />

      <AdminBugReportFilters
        assignedFilter={reports.assignedFilter}
        dashboard={reports.dashboard}
        search={reports.search}
        severityFilter={reports.severityFilter}
        statusFilter={reports.statusFilter}
        onAssignedFilterChange={(value) => {
          reports.setAssignedFilter(value);
          reports.setPage(1);
        }}
        onReset={reports.resetFilters}
        onSearchChange={(value) => {
          reports.setSearch(value);
          reports.setPage(1);
        }}
        onSeverityFilterChange={(value) => {
          reports.setSeverityFilter(value);
          reports.setPage(1);
        }}
        onStatusFilterChange={(value) => {
          reports.setStatusFilter(value);
          reports.setPage(1);
        }}
        onUseMyReports={reports.useMyReports}
      />

      {reports.isLoading ? (
        <p className="sr-only">Chargement des signalements...</p>
      ) : reports.error ? (
        <p className="text-red-600">Erreur lors du chargement des signalements.</p>
      ) : reports.reports.length === 0 ? (
        <p className="rounded-lg border border-stone-200 bg-white p-8 text-center text-stone-500">Aucun signalement ne correspond à ces critères.</p>
      ) : (
        <AdminBugReportsTable
          dashboard={reports.dashboard}
          reports={reports.reports}
          onAssign={reports.assignReport}
          onDelete={reports.deleteReport}
          onOpen={reports.openModal}
          onStatusChange={reports.updateReportStatus}
        />
      )}

      <PaginationControls
        page={reports.page}
        {...(reports.meta ? { total: reports.meta.total } : {})}
        totalLabel="signalement"
        totalPages={reports.meta?.totalPages ?? 1}
        onPageChange={reports.setPage}
      />

      <AdminBugReportDetailDialog
        activities={reports.activities}
        commentPage={reports.commentPage}
        comments={reports.comments}
        commentsMeta={reports.commentsMeta}
        duplicateOfId={reports.duplicateOfId}
        duplicatePending={reports.duplicatePending}
        duplicateReason={reports.duplicateReason}
        loadingComments={reports.loadingComments}
        newCommentText={reports.newCommentText}
        postCommentPending={reports.postCommentPending}
        report={reports.activeReport}
        onClose={reports.closeModal}
        onCommentPageChange={reports.setCommentPage}
        onDuplicateIdChange={reports.setDuplicateOfId}
        onDuplicateReasonChange={reports.setDuplicateReason}
        onDuplicateSubmit={reports.duplicateReport}
        onNewCommentTextChange={reports.setNewCommentText}
        onPostComment={reports.postComment}
      />
    </div>
  );
};
