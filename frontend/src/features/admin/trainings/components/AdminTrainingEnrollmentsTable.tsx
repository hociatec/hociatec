import type { TrainingEnrollmentDto } from '@/features/trainings/publicApi';
import { formatEuroCents, formatFrenchDateTime } from '@/shared/lib/formatters';
import { AdminListState, AdminTableShell } from '@/shared/components/admin/AdminDataView';
import { useAdminPagination } from '@/shared/hooks/useAdminPagination';
import { PaginationControls } from '@/shared/components/ui/PaginationControls';

export const AdminTrainingEnrollmentsTable = ({
  enrollments,
}: {
  enrollments: TrainingEnrollmentDto[];
}) => {
  const enrollmentsPagination = useAdminPagination(enrollments);

  return (
    <AdminListState
      loading={false}
      isEmpty={enrollments.length === 0}
      loadingLabel=""
      emptyLabel="Aucune inscription."
    >
      <AdminTableShell>
        <table className="catalog-admin-table">
          <thead>
            <tr>
              <th>Formation</th>
              <th>Créneau</th>
              <th>Prix</th>
              <th>Statut</th>
            </tr>
          </thead>
          <tbody>
            {enrollmentsPagination.paginatedItems.map((enrollment) => (
              <tr key={enrollment.id}>
                <td>{enrollment.session.training.title}</td>
                <td>{formatFrenchDateTime(enrollment.scheduledStartsAt)}</td>
                <td>{formatEuroCents(enrollment.priceCents)}</td>
                <td>{enrollment.statusLabel}</td>
              </tr>
            ))}
          </tbody>
        </table>
      </AdminTableShell>
      <PaginationControls
        page={enrollmentsPagination.page}
        total={enrollmentsPagination.total}
        totalLabel="inscription"
        totalPages={enrollmentsPagination.totalPages}
        onPageChange={enrollmentsPagination.setPage}
      />
    </AdminListState>
  );
};
