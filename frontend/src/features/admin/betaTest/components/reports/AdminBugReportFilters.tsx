import type { AdminBugReportDashboardDto } from '../../api';
import { bugReportStatusLabels, severityLabels } from '@/features/betaTest/publicApi';

interface AdminBugReportFiltersProps {
  assignedFilter: string;
  dashboard: AdminBugReportDashboardDto | undefined;
  search: string;
  severityFilter: string;
  statusFilter: string;
  onAssignedFilterChange: (value: string) => void;
  onReset: () => void;
  onSearchChange: (value: string) => void;
  onSeverityFilterChange: (value: string) => void;
  onStatusFilterChange: (value: string) => void;
  onUseMyReports: () => void;
}

export const AdminBugReportFilters = ({
  assignedFilter,
  dashboard,
  search,
  severityFilter,
  statusFilter,
  onAssignedFilterChange,
  onReset,
  onSearchChange,
  onSeverityFilterChange,
  onStatusFilterChange,
  onUseMyReports,
}: AdminBugReportFiltersProps) => (
  <section className="mb-6 rounded-2xl border border-stone-200 bg-stone-50 p-4">
    <div className="grid gap-4 md:grid-cols-5">
      <label className="text-sm font-semibold text-stone-700">
        Recherche
        <input
          className="mt-1 w-full rounded-lg border border-stone-300 bg-white p-2 font-normal"
          value={search}
          onChange={(event) => onSearchChange(event.target.value)}
          placeholder="Titre, description, email"
        />
      </label>
      <label className="text-sm font-semibold text-stone-700">
        État
        <select
          className="mt-1 w-full rounded-lg border border-stone-300 bg-white p-2 font-normal"
          value={statusFilter}
          onChange={(event) => onStatusFilterChange(event.target.value)}
        >
          <option value="">Tous</option>
          {Object.entries(bugReportStatusLabels).map(([value, label]) => <option key={value} value={value}>{label}</option>)}
        </select>
      </label>
      <label className="text-sm font-semibold text-stone-700">
        Gravité
        <select
          className="mt-1 w-full rounded-lg border border-stone-300 bg-white p-2 font-normal"
          value={severityFilter}
          onChange={(event) => onSeverityFilterChange(event.target.value)}
        >
          <option value="">Toutes</option>
          {Object.entries(severityLabels).map(([value, label]) => <option key={value} value={value}>{label}</option>)}
        </select>
      </label>
      <label className="text-sm font-semibold text-stone-700">
        Responsable
        <select
          className="mt-1 w-full rounded-lg border border-stone-300 bg-white p-2 font-normal"
          value={assignedFilter}
          onChange={(event) => onAssignedFilterChange(event.target.value)}
        >
          <option value="">Tous</option>
          {dashboard?.admins.map((admin) => <option key={admin.id} value={admin.id}>{admin.name} · {admin.email}</option>)}
        </select>
      </label>
      <div className="flex items-end gap-2">
        <button type="button" onClick={onUseMyReports} className="rounded-lg border border-brand-100 bg-white px-3 py-2 text-sm font-semibold text-brand-700">Mes signalements</button>
        <button type="button" onClick={onReset} className="rounded-lg border border-stone-200 bg-white px-3 py-2 text-sm font-semibold text-stone-700">Réinitialiser</button>
      </div>
    </div>
  </section>
);
