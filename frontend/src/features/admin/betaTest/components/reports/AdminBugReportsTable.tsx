import { MessageSquare, Trash2 } from 'lucide-react';

import type { AdminBugReportDashboardDto, AdminBugReportDto } from '../../api';
import { bugReportStatusLabels, formatBetaLabel, formatDate, severityLabels } from '@/features/betaTest/publicApi';
import { bugReportBadgeClassName } from './adminBugReportUi';
import { BUG_REPORT_STATUSES, isContractValue, type BugReportStatus } from '@/shared/contracts/statuses';
import { parseNullablePositiveInteger } from '@/shared/lib/parsers';

interface AdminBugReportsTableProps {
  dashboard: AdminBugReportDashboardDto | undefined;
  reports: AdminBugReportDto[];
  onAssign: (id: number, assignedToId?: number | null) => void;
  onDelete: (id: number) => void;
  onOpen: (report: AdminBugReportDto) => void;
  onStatusChange: (id: number, status: BugReportStatus) => void;
}

export const AdminBugReportsTable = ({
  dashboard,
  reports,
  onAssign,
  onDelete,
  onOpen,
  onStatusChange,
}: AdminBugReportsTableProps) => (
  <div className="overflow-x-auto rounded-lg border border-stone-200 bg-white shadow-sm">
    <table className="w-full border-collapse text-left text-sm">
      <thead>
        <tr className="border-b border-stone-200 bg-stone-50 font-semibold text-stone-600">
          <th className="p-4">Signalement</th>
          <th className="p-4">Gravité</th>
          <th className="p-4">État</th>
          <th className="p-4">Responsable</th>
          <th className="p-4">Actions</th>
        </tr>
      </thead>
      <tbody className="divide-y divide-stone-200">
        {reports.map((report) => (
          <tr key={report.id} className="hover:bg-stone-50">
            <td className="p-4">
              <button type="button" onClick={() => onOpen(report)} className="text-left font-bold text-brand-900 underline-offset-4 hover:underline">{report.title}</button>
              <p className="mt-1 text-xs text-stone-500">Email : {report.reporter} · Date : {formatDate(report.createdAt)}</p>
              <p className="mt-2 max-w-xl line-clamp-2 text-stone-700">{report.description}</p>
              {report.duplicateOf && <p className="mt-2 text-xs font-semibold text-amber-700">Rattaché à : {report.duplicateOf.title}</p>}
            </td>
            <td className="p-4">
              <span className={`inline-flex rounded-lg px-3 py-2 text-xs font-semibold ring-1 ${bugReportBadgeClassName(report.severity)}`}>
                Gravité : {formatBetaLabel(report.severity, severityLabels)}
              </span>
            </td>
            <td className="p-4">
              <select
                className="rounded-lg border border-stone-300 bg-white p-2 text-xs"
                value={report.status}
                onChange={(event) => {
                  if (isContractValue(BUG_REPORT_STATUSES, event.target.value)) {
                    onStatusChange(report.id, event.target.value);
                  }
                }}
              >
                {Object.entries(bugReportStatusLabels).map(([value, label]) => <option key={value} value={value}>{label}</option>)}
              </select>
            </td>
            <td className="p-4">
              <select
                className="rounded-lg border border-stone-300 bg-white p-2 text-xs"
                value={report.assignedTo?.id ?? ''}
                onChange={(event) => {
                  const nextAssignedTo = event.target.value
                    ? parseNullablePositiveInteger(event.target.value)
                    : null;
                  onAssign(report.id, nextAssignedTo);
                }}
              >
                <option value="">Non assigné</option>
                {dashboard?.admins.map((admin) => <option key={admin.id} value={admin.id}>{admin.name}</option>)}
              </select>
            </td>
            <td className="p-4">
              <div className="flex flex-wrap gap-2">
                <button className="inline-flex items-center gap-1 rounded bg-stone-100 px-3 py-2 text-xs font-semibold text-stone-700 hover:bg-stone-200" onClick={() => onOpen(report)}><MessageSquare size={14} /> Suivre</button>
                <button
                  className="rounded bg-red-50 px-3 py-2 text-xs font-semibold text-red-700 hover:bg-red-100"
                  onClick={() => onDelete(report.id)}
                  aria-label={`Supprimer le signalement ${report.title}`}
                >
                  <Trash2 size={14} aria-hidden="true" />
                </button>
              </div>
            </td>
          </tr>
        ))}
      </tbody>
    </table>
  </div>
);
