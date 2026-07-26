import type { TrainingEnrollmentDto } from '@/features/trainings/api/trainingsApi';
import { formatEuroCents, formatFrenchDateTime } from '@/shared/lib/formatters';
import { AdminListState, AdminTableShell } from '@/shared/components/admin/AdminDataView';

export const AdminTrainingEnrollmentsTable = ({ enrollments }: { enrollments: TrainingEnrollmentDto[] }) => <AdminListState loading={false} isEmpty={enrollments.length === 0} loadingLabel="" emptyLabel="Aucune inscription."><AdminTableShell><table className="catalog-admin-table"><thead><tr><th>Formation</th><th>Créneau</th><th>Prix</th><th>Statut</th></tr></thead><tbody>{enrollments.map((enrollment) => <tr key={enrollment.id}><td>{enrollment.session.training.title}</td><td>{formatFrenchDateTime(enrollment.scheduledStartsAt)}</td><td>{formatEuroCents(enrollment.priceCents)}</td><td>{enrollment.statusLabel}</td></tr>)}</tbody></table></AdminTableShell></AdminListState>;
