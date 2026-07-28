import type {
  AdminBugReportActivityDto,
  AdminBugReportDto,
  BugReportCommentDto,
  PaginationMeta,
} from '../../api';
import { resolveBetaAttachmentUrl } from '../../api';
import { bugReportStatusLabels, formatBetaLabel, formatDate, severityLabels } from '@/features/betaTest/lib/betaLabels';
import { Dialog, DialogBackdrop, DialogPanel, DialogTitle } from '@/shared/components/ui/dialog';
import { activityLabel, bugReportBadgeClassName, terminalStates } from './adminBugReportUi';

interface AdminBugReportDetailDialogProps {
  activities: AdminBugReportActivityDto[];
  commentPage: number;
  comments: BugReportCommentDto[];
  commentsMeta: PaginationMeta | null;
  duplicateOfId: string;
  duplicatePending: boolean;
  duplicateReason: string;
  loadingComments: boolean;
  newCommentText: string;
  postCommentPending: boolean;
  report: AdminBugReportDto | undefined;
  onClose: () => void;
  onCommentPageChange: (updater: (page: number) => number) => void;
  onDuplicateIdChange: (value: string) => void;
  onDuplicateReasonChange: (value: string) => void;
  onDuplicateSubmit: (payload: { id: number; duplicateOfId: number; reason?: string }) => void;
  onNewCommentTextChange: (value: string) => void;
  onPostComment: () => void;
}

export const AdminBugReportDetailDialog = ({
  activities,
  commentPage,
  comments,
  commentsMeta,
  duplicateOfId,
  duplicatePending,
  duplicateReason,
  loadingComments,
  newCommentText,
  postCommentPending,
  report,
  onClose,
  onCommentPageChange,
  onDuplicateIdChange,
  onDuplicateReasonChange,
  onDuplicateSubmit,
  onNewCommentTextChange,
  onPostComment,
}: AdminBugReportDetailDialogProps) => {
  if (!report) return null;

  return (
    <Dialog open={Boolean(report)} onClose={onClose} className="relative z-50">
      <DialogBackdrop className="fixed inset-0 bg-brand-900/70" />
      <div className="fixed inset-0 flex items-center justify-center p-4">
        <DialogPanel className="flex max-h-[90vh] w-full max-w-5xl flex-col overflow-hidden rounded-xl bg-white shadow-xl">
          <header className="border-b border-stone-200 p-5">
            <button type="button" className="mb-3 rounded-lg border border-stone-200 px-4 py-2 text-sm font-semibold text-stone-700 hover:bg-stone-50" onClick={onClose}>Fermer</button>
            <DialogTitle className="text-2xl font-bold text-brand-900">{report.title}</DialogTitle>
            <p className="mt-1 text-sm text-stone-500">Email : {report.reporter} · Campagne : {report.campaign || 'Général'} · Date : {formatDate(report.createdAt)}</p>
          </header>

          <div className="grid flex-1 overflow-y-auto md:grid-cols-[1.1fr_0.9fr]">
            <section className="space-y-5 border-r border-stone-200 p-5">
              <div className="grid gap-3 text-sm sm:grid-cols-2">
                <p className={`rounded-lg px-3 py-2 ring-1 ${bugReportBadgeClassName(report.severity)}`}>Gravité : {formatBetaLabel(report.severity, severityLabels)}</p>
                <p className={`rounded-lg px-3 py-2 ring-1 ${bugReportBadgeClassName(report.status)}`}>État : {formatBetaLabel(report.status, bugReportStatusLabels)}</p>
                <p className="rounded-lg bg-stone-50 px-3 py-2 ring-1 ring-stone-200">Responsable : {report.assignedTo?.name ?? 'Non assigné'}</p>
                <p className="rounded-lg bg-stone-50 px-3 py-2 ring-1 ring-stone-200">Statut de traitement : {terminalStates.has(report.status) ? 'Terminé' : 'Ouvert'}</p>
              </div>
              {report.duplicateOf && <p className="rounded-lg bg-amber-50 p-3 text-sm text-amber-800 ring-1 ring-amber-200">Ce signalement est rattaché à : {report.duplicateOf.title}</p>}
              <section><h2 className="font-semibold text-stone-900">Description</h2><p className="mt-1 whitespace-pre-wrap text-sm leading-6 text-stone-700">{report.description}</p></section>
              <section><h2 className="font-semibold text-stone-900">Résultat attendu</h2><p className="mt-1 whitespace-pre-wrap text-sm leading-6 text-stone-700">{report.expectedBehavior || 'Non renseigné'}</p></section>
              <section><h2 className="font-semibold text-stone-900">Résultat constaté</h2><p className="mt-1 whitespace-pre-wrap text-sm leading-6 text-stone-700">{report.actualBehavior || 'Non renseigné'}</p></section>
              {(report.attachmentUrls ?? []).length > 0 && (
                <section>
                  <h2 className="font-semibold text-stone-900">Captures</h2>
                  <ul className="mt-2 space-y-1 text-sm">
                    {report.attachmentUrls.map((url, index) => <li key={url}><a className="text-brand-700 underline" href={resolveBetaAttachmentUrl(url)} target="_blank" rel="noreferrer">Ouvrir la capture {index + 1}</a></li>)}
                  </ul>
                </section>
              )}
              <form
                className="rounded-2xl border border-stone-200 bg-stone-50 p-4"
                onSubmit={(event) => {
                  event.preventDefault();
                  if (!duplicateOfId) return;
                  onDuplicateSubmit({ id: report.id, duplicateOfId: Number(duplicateOfId), reason: duplicateReason });
                }}
              >
                <h2 className="font-semibold text-stone-900">Rattacher comme doublon</h2>
                <div className="mt-3 grid gap-3 sm:grid-cols-[1fr_2fr_auto]">
                  <input className="rounded-lg border border-stone-300 bg-white p-2 text-sm" value={duplicateOfId} onChange={(event) => onDuplicateIdChange(event.target.value)} inputMode="numeric" placeholder="ID référence" />
                  <input className="rounded-lg border border-stone-300 bg-white p-2 text-sm" value={duplicateReason} onChange={(event) => onDuplicateReasonChange(event.target.value)} placeholder="Raison facultative" />
                  <button className="rounded-lg bg-brand-700 px-4 py-2 text-sm font-semibold text-white disabled:opacity-50" disabled={!duplicateOfId || duplicatePending}>Rattacher</button>
                </div>
              </form>
            </section>

            <section className="flex min-h-[520px] flex-col">
              <div className="border-b border-stone-200 p-4">
                <h2 className="font-semibold text-brand-900">Discussion</h2>
              </div>
              <div className="flex-1 space-y-3 overflow-y-auto bg-stone-50 p-4">
                {loadingComments ? <p className="text-sm text-stone-500">Chargement des messages...</p> : comments.length === 0 ? <p className="text-sm text-stone-500">Aucun message.</p> : comments.map((comment) => {
                  const authorLabel = comment.author.role === 'admin' ? 'Support Hociatec' : comment.author.email;
                  return <p key={comment.id} className="rounded-lg border border-stone-200 bg-white p-3 text-sm"><span className="font-semibold">{authorLabel}</span> <span className="text-stone-500">({new Date(comment.createdAt).toLocaleString()})</span> : {comment.content}</p>;
                })}
              </div>
              {commentsMeta && commentsMeta.totalPages > 1 && (
                <div className="flex items-center justify-between border-t border-stone-200 bg-white p-3 text-sm">
                  <button type="button" disabled={commentPage <= 1} onClick={() => onCommentPageChange((value) => Math.max(1, value - 1))} className="rounded border border-stone-200 px-3 py-2 font-semibold disabled:opacity-50">Précédents</button>
                  <span>Page {commentsMeta.page} sur {commentsMeta.totalPages}</span>
                  <button type="button" disabled={commentPage >= commentsMeta.totalPages} onClick={() => onCommentPageChange((value) => Math.min(commentsMeta.totalPages, value + 1))} className="rounded border border-stone-200 px-3 py-2 font-semibold disabled:opacity-50">Suivants</button>
                </div>
              )}
              <form onSubmit={(event) => { event.preventDefault(); if (newCommentText.trim()) onPostComment(); }} className="flex gap-2 border-t border-stone-200 p-4">
                <input className="flex-1 rounded-lg border border-stone-300 p-3 text-sm" value={newCommentText} onChange={(event) => onNewCommentTextChange(event.target.value)} placeholder="Rédiger une réponse..." />
                <button className="rounded-lg bg-brand-700 px-4 py-3 text-sm font-semibold text-white disabled:opacity-50" disabled={postCommentPending || !newCommentText.trim()}>{postCommentPending ? 'Envoi...' : 'Répondre'}</button>
              </form>
              <div className="max-h-48 overflow-y-auto border-t border-stone-200 p-4">
                <h2 className="font-semibold text-brand-900">Journal technique</h2>
                <div className="mt-2 space-y-2 text-xs text-stone-600">
                  {activities.length === 0 ? <p>Aucune action journalisée.</p> : activities.map((activity) => <p key={activity.id} className="rounded bg-stone-50 p-2">{activityLabel(activity.action)} · {activity.actor?.email ?? 'Système'} · {new Date(activity.createdAt).toLocaleString()} {activity.fromValue || activity.toValue ? `· ${activity.fromValue ?? 'vide'} → ${activity.toValue ?? 'vide'}` : ''}</p>)}
                </div>
              </div>
            </section>
          </div>
        </DialogPanel>
      </div>
    </Dialog>
  );
};
