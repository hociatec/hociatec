import { MessageSquare } from 'lucide-react';

import type { AdminBugReportDto, AdminCampaignDto } from '../../api';
import {
  bugReportStatusLabels,
  campaignStateLabels,
  formatBetaLabel,
  formatDate,
  severityLabels,
} from '@/features/betaTest/publicApi';
import { Dialog, DialogBackdrop, DialogPanel, DialogTitle } from '@/shared/components/ui/dialog';
import { clampAtLeast, clampWithin } from '@/shared/lib/number';

interface AdminCampaignDetailDialogProps {
  campaign: AdminCampaignDto | null;
  open: boolean;
  reportsPage: number;
  reportsPageCount: number;
  reportsPerPage: number;
  visibleReports: AdminBugReportDto[];
  onClose: () => void;
  onReportsPageChange: (updater: (page: number) => number) => void;
  onSelectReport: (reportId: number) => void;
}

export const AdminCampaignDetailDialog = ({
  campaign,
  open,
  reportsPage,
  reportsPageCount,
  reportsPerPage,
  visibleReports,
  onClose,
  onReportsPageChange,
  onSelectReport,
}: AdminCampaignDetailDialogProps) => {
  if (!campaign) return null;

  const reports = campaign.reports ?? [];

  return (
    <Dialog open={open} onClose={() => undefined} className="relative z-50">
      <DialogBackdrop className="fixed inset-0 bg-brand-900/70" />
      <div
        className="fixed inset-0 flex items-center justify-center p-4"
        onKeyDownCapture={(event) => {
          if (event.key === 'Escape') {
            event.preventDefault();
            event.stopPropagation();
          }
        }}
      >
        <DialogPanel className="max-h-[90vh] w-full max-w-4xl overflow-y-auto rounded-xl border border-brand-100 bg-white p-6 shadow-2xl">
          <header className="border-b border-stone-200 pb-4">
            <button
              type="button"
              onClick={onClose}
              className="mb-4 rounded-lg border border-brand-100 px-4 py-2 text-sm font-semibold text-stone-700 transition hover:bg-brand-50 focus:outline-none focus:ring-4 focus:ring-brand-100"
            >
              Fermer
            </button>
            <DialogTitle className="text-xl font-bold text-stone-900">
              {campaign.name}
            </DialogTitle>
          </header>

          <div className="mt-6 space-y-6">
            <section className="grid gap-3 sm:grid-cols-3">
              <article className="rounded-lg border border-stone-200 bg-stone-50 p-4">
                <p className="text-sm text-stone-600">
                  Inscrits : <span className="font-semibold text-stone-900">{campaign.enrolledCount ?? 0}</span>
                </p>
              </article>
              <article className="rounded-lg border border-stone-200 bg-stone-50 p-4">
                <p className="text-sm text-stone-600">
                  Rapports : <span className="font-semibold text-stone-900">{campaign.reportCount ?? reports.length}</span>
                </p>
              </article>
              <article className="rounded-lg border border-stone-200 bg-stone-50 p-4">
                <p className="text-sm text-stone-600">
                  État : <span className="font-semibold text-stone-900">{formatBetaLabel(campaign.status, campaignStateLabels)}</span>
                </p>
              </article>
            </section>

            <section className="space-y-3 text-sm text-stone-700">
              <p className="whitespace-pre-wrap">
                <span className="font-semibold text-stone-900">Description : </span>{campaign.description}
              </p>
              <p><span className="font-semibold text-stone-900">Date de création : </span>{formatDate(campaign.createdAt)}</p>
              <p><span className="font-semibold text-stone-900">Date de début : </span>{formatDate(campaign.startsAt)}</p>
              <p><span className="font-semibold text-stone-900">Date de fin : </span>{formatDate(campaign.endsAt)}</p>
            </section>

            <section className="rounded-lg border border-stone-200">
              <div className="border-b border-stone-200 px-4 py-3">
                <h2 className="text-base font-semibold text-stone-900">Rapports liés à la campagne</h2>
              </div>
              {reports.length ? (
                <div className="divide-y divide-stone-200">
                  {visibleReports.map((report) => (
                    <article key={report.id} className="p-4">
                      <div className="flex flex-wrap items-start justify-between gap-3">
                        <h3 className="font-semibold text-stone-900">{report.title}</h3>
                        <button
                          type="button"
                          onClick={() => onSelectReport(report.id)}
                          className="inline-flex items-center gap-2 rounded-lg border border-brand-100 px-3 py-2 text-sm font-semibold text-brand-700 transition hover:bg-brand-50"
                        >
                          <MessageSquare size={16} />
                          <span>Suivre les échanges</span>
                        </button>
                      </div>
                      <div className="mt-2 grid gap-2 text-sm text-stone-600 sm:grid-cols-2">
                        <p><span className="font-semibold text-stone-900">Auteur : </span>{report.reporter}</p>
                        <p><span className="font-semibold text-stone-900">Date : </span>{formatDate(report.createdAt)}</p>
                        <p><span className="font-semibold text-stone-900">Gravité : </span>{formatBetaLabel(report.severity, severityLabels)}</p>
                        <p><span className="font-semibold text-stone-900">État : </span>{formatBetaLabel(report.status, bugReportStatusLabels)}</p>
                      </div>
                      <p className="mt-3 line-clamp-3 text-sm text-stone-700">{report.description}</p>
                    </article>
                  ))}
                </div>
              ) : (
                <p className="p-4 text-sm text-stone-500">Aucun rapport lié à cette campagne.</p>
              )}
              {reports.length > reportsPerPage ? (
                <div className="flex flex-wrap items-center justify-between gap-3 border-t border-stone-200 px-4 py-3 text-sm">
                  <p className="text-stone-600">Page {reportsPage} sur {reportsPageCount}</p>
                  <div className="flex gap-2">
                    <button
                      type="button"
                      disabled={reportsPage === 1}
                      onClick={() => onReportsPageChange((page) => clampAtLeast(page - 1, 1))}
                      className="rounded-lg border border-brand-100 px-3 py-2 font-semibold text-stone-700 transition hover:bg-brand-50 disabled:cursor-not-allowed disabled:opacity-50"
                    >
                      Précédent
                    </button>
                    <button
                      type="button"
                      disabled={reportsPage === reportsPageCount}
                      onClick={() =>
                        onReportsPageChange((page) => clampWithin(page + 1, 1, reportsPageCount))
                      }
                      className="rounded-lg border border-brand-100 px-3 py-2 font-semibold text-stone-700 transition hover:bg-brand-50 disabled:cursor-not-allowed disabled:opacity-50"
                    >
                      Suivant
                    </button>
                  </div>
                </div>
              ) : null}
            </section>
          </div>
        </DialogPanel>
      </div>
    </Dialog>
  );
};
