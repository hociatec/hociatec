import { MessageSquare, Plus } from 'lucide-react';

import type { BetaCampaign, BugReport } from '../../api/betaApi';
import {
  bugReportStatusLabels,
  campaignStateLabels,
  formatBetaLabel,
  formatDate,
  severityLabels,
} from '../../lib/betaLabels';
import { Dialog, DialogBackdrop, DialogPanel, DialogTitle } from '@/shared/components/ui/dialog';
import { badgeClassName } from './betaDashboardUtils';

interface BetaCampaignDetailsDialogProps {
  canCreateReport: boolean;
  campaign: BetaCampaign | null;
  reports: BugReport[];
  onClose: () => void;
  onCreateReport: (campaign: BetaCampaign) => void;
  onOpenReport: (reportId: number) => void;
}

export const BetaCampaignDetailsDialog = ({
  canCreateReport,
  campaign,
  reports,
  onClose,
  onCreateReport,
  onOpenReport,
}: BetaCampaignDetailsDialogProps) => {
  if (!campaign) return null;

  return (
    <Dialog open={Boolean(campaign)} onClose={onClose} className="relative z-50">
      <DialogBackdrop className="fixed inset-0 bg-brand-900/70" />
      <div className="fixed inset-0 flex items-center justify-center px-4 py-6">
        <DialogPanel className="max-h-[90vh] w-full max-w-2xl overflow-y-auto rounded-xl border border-brand-100 bg-white p-6 shadow-2xl">
          <header className="space-y-4">
            <div className="flex flex-wrap items-start justify-between gap-4">
              <div>
                <button
                  type="button"
                  className="mb-3 inline-flex items-center justify-center rounded-lg border border-stone-200 px-4 py-3 text-sm font-semibold text-stone-700 transition hover:bg-stone-50 focus:outline-none focus:ring-4 focus:ring-stone-100"
                  onClick={onClose}
                >
                  Fermer
                </button>
                <DialogTitle className="text-2xl font-bold text-brand-900">
                  {campaign.name}
                </DialogTitle>
              </div>
              <div className="flex items-start gap-2">
                <div className="max-w-xs text-right">
                  <p className="mb-2 text-sm leading-5 text-stone-600">
                    {canCreateReport
                      ? 'Consultez les consignes avant d’envoyer un signalement lié à cette campagne.'
                      : 'Cette campagne n’est plus ouverte aux signalements.'}
                  </p>
                  <button
                    type="button"
                    onClick={() => onCreateReport(campaign)}
                    disabled={!canCreateReport}
                    className="inline-flex items-center justify-center gap-2 rounded-lg bg-brand-600 px-4 py-3 text-sm font-semibold text-white shadow-sm transition hover:bg-brand-700 focus:outline-none focus:ring-4 focus:ring-brand-100 disabled:cursor-not-allowed disabled:opacity-50"
                  >
                    <Plus size={16} />
                    Envoyer un signalement
                  </button>
                </div>
              </div>
            </div>
          </header>

          <div className="mt-6 space-y-5">
            <div className="rounded-2xl bg-brand-50 p-5">
              <h3 className="font-semibold text-brand-900">Informations de campagne</h3>
              <p className="mt-2 whitespace-pre-wrap text-sm leading-6 text-stone-700">
                {campaign.description}
              </p>
            </div>
            <dl className="grid gap-3 text-sm sm:grid-cols-3">
              <div className="rounded-xl border border-stone-200 p-3">
                <dt className="text-xs font-semibold uppercase tracking-[0.12em] text-stone-500">État</dt>
                <dd className="mt-1 font-semibold text-emerald-700">
                  {formatBetaLabel(campaign.status, {
                    ...campaignStateLabels,
                    active: canCreateReport ? 'Active' : 'Clôturée',
                  })}
                </dd>
              </div>
              <div className="rounded-xl border border-stone-200 p-3">
                <dt className="text-xs font-semibold uppercase tracking-[0.12em] text-stone-500">Début</dt>
                <dd className="mt-1 text-stone-800">{formatDate(campaign.startsAt)}</dd>
              </div>
              <div className="rounded-xl border border-stone-200 p-3">
                <dt className="text-xs font-semibold uppercase tracking-[0.12em] text-stone-500">Fin</dt>
                <dd className="mt-1 text-stone-800">{formatDate(campaign.endsAt)}</dd>
              </div>
            </dl>
            <section className="rounded-2xl border border-stone-200 bg-white p-5">
              <div className="flex flex-wrap items-center justify-between gap-2">
                <h3 className="font-semibold text-brand-900">Suivis liés à cette campagne</h3>
                <span className="rounded-full bg-stone-50 px-3 py-1 text-xs font-semibold text-stone-600 ring-1 ring-stone-200">
                  {reports.length} signalement{reports.length > 1 ? 's' : ''}
                </span>
              </div>
              {reports.length === 0 ? (
                <p className="mt-3 text-sm text-stone-600">
                  Aucun signalement lié à cette campagne pour le moment.
                </p>
              ) : (
                <div className="mt-4 grid gap-3">
                  {reports.map((report) => (
                    <article key={report.id} className="rounded-xl border border-stone-100 bg-stone-50 p-4">
                      <div className="flex flex-wrap items-start justify-between gap-3">
                        <div>
                          <h4 className="font-semibold text-brand-900">{report.title}</h4>
                          <p className="mt-1 text-xs text-stone-500">
                            Date du signalement : {formatDate(report.createdAt)}
                          </p>
                        </div>
                        <div className="grid gap-2 text-xs font-semibold">
                          <p className={`rounded-lg px-3 py-2 ring-1 ${badgeClassName(report.severity)}`}>
                            Priorité : {formatBetaLabel(report.severity, severityLabels)}
                          </p>
                          <p className={`rounded-lg px-3 py-2 ring-1 ${badgeClassName(report.status)}`}>
                            État : {formatBetaLabel(report.status, bugReportStatusLabels)}
                          </p>
                        </div>
                      </div>
                      <div className="mt-3 flex justify-end">
                        <button
                          type="button"
                          className="inline-flex items-center gap-2 rounded-lg border border-brand-100 bg-white px-4 py-2 text-xs font-semibold text-brand-700 transition hover:bg-brand-50"
                          onClick={() => onOpenReport(report.id)}
                        >
                          <MessageSquare size={14} />
                          Ouvrir le suivi
                        </button>
                      </div>
                    </article>
                  ))}
                </div>
              )}
            </section>
          </div>
        </DialogPanel>
      </div>
    </Dialog>
  );
};
