import { MessageSquare, X } from 'lucide-react';

import {
  type BugReport,
  type BugReportComment,
  type PaginationMeta,
  resolveBetaAttachmentUrl,
} from '../../api/betaApi';
import {
  bugReportStatusLabels,
  formatBetaLabel,
  formatDate,
  severityLabels,
} from '../../lib/betaLabels';
import { Dialog, DialogBackdrop, DialogPanel, DialogTitle } from '@/shared/components/ui/dialog';
import { badgeClassName } from './betaDashboardUtils';

interface BetaReportFollowUpDialogProps {
  commentPage: number;
  comments: BugReportComment[];
  commentsMeta: PaginationMeta | null;
  loadingComments: boolean;
  newCommentText: string;
  open: boolean;
  report: BugReport;
  sending: boolean;
  onClose: () => void;
  onCommentPageChange: (updater: (page: number) => number) => void;
  onCommentTextChange: (value: string) => void;
  onSubmit: (event: React.FormEvent) => void;
}

export const BetaReportFollowUpDialog = ({
  commentPage,
  comments,
  commentsMeta,
  loadingComments,
  newCommentText,
  open,
  report,
  sending,
  onClose,
  onCommentPageChange,
  onCommentTextChange,
  onSubmit,
}: BetaReportFollowUpDialogProps) => (
  <Dialog open={open} onClose={onClose} className="relative z-50">
    <DialogBackdrop className="fixed inset-0 bg-brand-900/70" />
    <div className="fixed inset-0 flex items-center justify-center px-4 py-6">
      <DialogPanel className="flex max-h-[88vh] w-full max-w-3xl flex-col rounded-xl border border-brand-100 bg-white shadow-2xl">
        <header className="border-b border-stone-200 p-5">
          <div className="flex items-start justify-between gap-4">
            <div>
              <p className="text-xs font-semibold uppercase tracking-[0.2em] text-brand-700">
                Suivi du signalement
              </p>
              <DialogTitle className="mt-1 text-xl font-bold text-brand-900">
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

        <div className="max-h-56 overflow-y-auto border-b border-stone-200 bg-stone-50 p-5 text-sm text-stone-700">
          <div className="grid gap-4 md:grid-cols-2">
            <section className="md:col-span-2">
              <h3 className="font-semibold text-stone-900">Signalement initial</h3>
              <p className="mt-1 whitespace-pre-wrap leading-6">{report.description}</p>
            </section>
            {report.expectedBehavior && (
              <section>
                <h3 className="font-semibold text-stone-900">Résultat attendu</h3>
                <p className="mt-1 whitespace-pre-wrap leading-6">{report.expectedBehavior}</p>
              </section>
            )}
            {report.actualBehavior && (
              <section>
                <h3 className="font-semibold text-stone-900">Résultat constaté</h3>
                <p className="mt-1 whitespace-pre-wrap leading-6">{report.actualBehavior}</p>
              </section>
            )}
          </div>
          {(report.attachmentUrls ?? []).length > 0 && (
            <div className="mt-4">
              <strong>Captures :</strong>
              <ul className="mt-1 space-y-1">
                {(report.attachmentUrls ?? []).map((url, index) => (
                  <li key={url}>
                    <a className="text-brand-700 underline" href={resolveBetaAttachmentUrl(url)} target="_blank" rel="noreferrer">
                      Ouvrir la capture {index + 1}
                    </a>
                  </li>
                ))}
              </ul>
            </div>
          )}
        </div>

        <div className="flex items-center gap-2 border-b border-stone-200 bg-white px-5 py-3">
          <MessageSquare size={16} className="text-brand-700" />
          <h3 className="text-sm font-bold text-brand-900">Conversation</h3>
        </div>
        <div className="flex-1 space-y-4 overflow-y-auto bg-stone-50/50 p-4">
          {loadingComments ? (
            <p className="text-center text-sm text-stone-500">Chargement des messages...</p>
          ) : comments.length === 0 ? (
            <p className="py-4 text-center text-sm text-stone-400">Pas encore de message. L'équipe technique vous répondra très bientôt ici !</p>
          ) : (
            comments.map((comment) => {
              const isAdminMessage = comment.author.role === 'admin';
              const authorLabel = isAdminMessage ? 'Support Hociatec' : 'Vous';
              const messageDate = new Date(comment.createdAt).toLocaleString();

              return (
                <div
                  key={comment.id}
                  className={`flex max-w-[85%] flex-col ${
                    !isAdminMessage ? 'ml-auto items-end' : 'mr-auto items-start'
                  }`}
                >
                  <div
                    className={`rounded-lg p-3 text-sm ${
                      !isAdminMessage
                        ? 'rounded-br-none bg-brand-700 text-white'
                        : 'rounded-bl-none border border-stone-200 bg-white text-stone-800'
                    }`}
                  >
                    <p className="whitespace-pre-wrap">
                      <span className="font-semibold">{authorLabel}</span>
                      {' '}
                      <span>({messageDate})</span>
                      {' : '}
                      {comment.content}
                    </p>
                  </div>
                </div>
              );
            })
          )}
        </div>
        {commentsMeta && commentsMeta.totalPages > 1 && (
          <div className="flex items-center justify-between border-t border-stone-200 bg-white px-5 py-3 text-sm text-stone-600">
            <button
              type="button"
              disabled={commentPage <= 1}
              onClick={() => onCommentPageChange((page) => Math.max(1, page - 1))}
              className="rounded-lg border border-stone-200 px-3 py-2 font-semibold disabled:opacity-50"
            >
              Messages précédents
            </button>
            <span>Page {commentsMeta.page} sur {commentsMeta.totalPages} · {commentsMeta.total} message{commentsMeta.total > 1 ? 's' : ''}</span>
            <button
              type="button"
              disabled={commentPage >= commentsMeta.totalPages}
              onClick={() => onCommentPageChange((page) => Math.min(commentsMeta.totalPages, page + 1))}
              className="rounded-lg border border-stone-200 px-3 py-2 font-semibold disabled:opacity-50"
            >
              Messages suivants
            </button>
          </div>
        )}

        <form onSubmit={onSubmit} className="flex gap-2 border-t border-stone-200 p-4">
          <input
            type="text"
            placeholder="Écrire un message à l'équipe..."
            className="flex-1 rounded-lg border border-stone-300 p-3 text-sm focus:border-brand-700 focus:outline-none"
            value={newCommentText}
            onChange={(event) => onCommentTextChange(event.target.value)}
          />
          <button
            type="submit"
            disabled={sending || !newCommentText.trim()}
            className="rounded-lg bg-brand-700 px-4 py-3 text-sm font-semibold text-white hover:bg-brand-800 disabled:opacity-50"
          >
            {sending ? 'Envoi...' : 'Envoyer'}
          </button>
        </form>
      </DialogPanel>
    </div>
  </Dialog>
);
