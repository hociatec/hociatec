import { X } from 'lucide-react';

import type { BugReport } from '../../../api/betaApi';
import {
  bugReportStatusLabels,
  formatBetaLabel,
  formatDate,
  severityLabels,
} from '../../../lib/betaLabels';
import { DialogTitle } from '@/shared/components/ui/dialog';
import { badgeClassName } from '../betaDashboardUtils';

interface BetaReportFollowUpHeaderProps {
  report: BugReport;
  onClose: () => void;
}

export const BetaReportFollowUpHeader = ({ report, onClose }: BetaReportFollowUpHeaderProps) => (
  <header className="border-b border-stone-200 p-5">
    <div className="flex items-start justify-between gap-4">
      <div>
        <DialogTitle className="text-xl font-bold text-brand-900">
          {report.title}
        </DialogTitle>
        <p className="mt-1 text-xs text-stone-500">
          {report.campaign ? `Campagne : ${report.campaign}` : 'Signalement général'}.
          {' '}
          Date du signalement : {formatDate(report.createdAt)}
        </p>
      </div>
      <button
        type="button"
        className="rounded-full p-1 text-stone-500 transition hover:bg-stone-100 hover:text-stone-700"
        onClick={onClose}
        aria-label="Fermer le suivi"
      >
        <X size={20} />
      </button>
    </div>
    <div className="mt-4 grid gap-2 text-xs font-semibold">
      <p className={`rounded-lg px-3 py-2 ring-1 ${badgeClassName(report.severity)}`}>
        Gravité : {formatBetaLabel(report.severity, severityLabels)}
      </p>
      <p className={`rounded-lg px-3 py-2 ring-1 ${badgeClassName(report.status)}`}>
        État : {formatBetaLabel(report.status, bugReportStatusLabels)}
      </p>
    </div>
    {report.duplicateOf && (
      <p className="mt-3 rounded-lg bg-amber-50 px-3 py-2 text-sm text-amber-800 ring-1 ring-amber-200">
        Rattaché au signalement : {report.duplicateOf.title}
      </p>
    )}
  </header>
);
