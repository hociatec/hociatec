import type { AdminBugReportDto, BugReportCommentDto, PaginationMeta } from '../../api';
import { Dialog, DialogBackdrop, DialogPanel, DialogTitle } from '@/shared/components/ui/dialog';

interface AdminCampaignReportMessagesDialogProps {
  comments: BugReportCommentDto[];
  commentsMeta: PaginationMeta | null;
  commentsPage: number;
  loadingComments: boolean;
  newCommentText: string;
  report: AdminBugReportDto | undefined;
  sending: boolean;
  onClose: () => void;
  onCommentTextChange: (value: string) => void;
  onCommentsPageChange: (updater: (page: number) => number) => void;
  onSubmit: (event: React.FormEvent) => void;
}

export const AdminCampaignReportMessagesDialog = ({
  comments,
  commentsMeta,
  commentsPage,
  loadingComments,
  newCommentText,
  report,
  sending,
  onClose,
  onCommentTextChange,
  onCommentsPageChange,
  onSubmit,
}: AdminCampaignReportMessagesDialogProps) => {
  if (!report) return null;

  return (
    <Dialog open={Boolean(report)} onClose={onClose} className="relative z-[60]">
      <DialogBackdrop className="fixed inset-0 bg-brand-900/75" />
      <div className="fixed inset-0 flex items-center justify-center p-4">
        <DialogPanel className="flex max-h-[85vh] w-full max-w-2xl flex-col rounded-xl border border-brand-100 bg-white shadow-2xl">
          <header className="border-b border-stone-200 p-4">
            <button
              type="button"
              onClick={onClose}
              className="mb-4 rounded-lg border border-brand-100 px-4 py-2 text-sm font-semibold text-stone-700 transition hover:bg-brand-50 focus:outline-none focus:ring-4 focus:ring-brand-100"
            >
              Fermer
            </button>
            <DialogTitle className="text-lg font-bold text-stone-900">
              Suivi des échanges : {report.title}
            </DialogTitle>
            <p className="mt-1 text-sm text-stone-600">Auteur : {report.reporter}</p>
          </header>

          <div className="max-h-28 overflow-y-auto border-b border-stone-200 bg-stone-50 p-4 text-sm text-stone-700">
            <p>
              <span className="font-semibold text-stone-900">Description initiale : </span>
              {report.description}
            </p>
          </div>

          <div className="flex-1 space-y-4 overflow-y-auto bg-stone-50/60 p-4">
            {loadingComments ? (
              <p className="text-center text-sm text-stone-500">Chargement des messages...</p>
            ) : comments.length === 0 ? (
              <p className="py-4 text-center text-sm text-stone-500">Aucun message pour le moment.</p>
            ) : (
              comments.map((comment) => {
                const isAdminMessage = comment.author.role === 'admin';

                return (
                  <article
                    key={comment.id}
                    className={`max-w-[85%] ${isAdminMessage ? 'ml-auto text-right' : 'mr-auto text-left'}`}
                  >
                    <p className="mb-1 text-xs text-stone-500">
                      {comment.author.firstName} {comment.author.lastName} ({new Date(comment.createdAt).toLocaleString('fr-FR')}) :
                    </p>
                    <div
                      className={`rounded-lg p-3 text-sm ${
                        isAdminMessage
                          ? 'bg-brand-700 text-white'
                          : 'border border-stone-200 bg-white text-stone-800'
                      }`}
                    >
                      <p className="whitespace-pre-wrap">{comment.content}</p>
                    </div>
                  </article>
                );
              })
            )}
          </div>

          {commentsMeta && commentsMeta.totalPages > 1 ? (
            <div className="flex items-center justify-between border-t border-stone-200 bg-white px-4 py-3 text-sm text-stone-600">
              <button
                type="button"
                disabled={commentsPage <= 1}
                onClick={() => onCommentsPageChange((page) => Math.max(1, page - 1))}
                className="rounded-lg border border-brand-100 px-3 py-2 font-semibold transition hover:bg-brand-50 disabled:cursor-not-allowed disabled:opacity-50"
              >
                Messages précédents
              </button>
              <span>Page {commentsMeta.page} sur {commentsMeta.totalPages}</span>
              <button
                type="button"
                disabled={commentsPage >= commentsMeta.totalPages}
                onClick={() => onCommentsPageChange((page) => Math.min(commentsMeta.totalPages, page + 1))}
                className="rounded-lg border border-brand-100 px-3 py-2 font-semibold transition hover:bg-brand-50 disabled:cursor-not-allowed disabled:opacity-50"
              >
                Messages suivants
              </button>
            </div>
          ) : null}

          <form onSubmit={onSubmit} className="flex gap-2 border-t border-stone-200 p-4">
            <input
              type="text"
              placeholder="Rédiger votre réponse..."
              className="flex-1 rounded-lg border border-stone-300 p-3 text-sm focus:border-brand-700 focus:outline-none"
              value={newCommentText}
              onChange={(event) => onCommentTextChange(event.target.value)}
            />
            <button
              type="submit"
              disabled={sending || !newCommentText.trim()}
              className="rounded-lg bg-brand-700 px-4 py-3 text-sm font-semibold text-white hover:bg-brand-800 disabled:opacity-50"
            >
              {sending ? 'Envoi...' : 'Répondre'}
            </button>
          </form>
        </DialogPanel>
      </div>
    </Dialog>
  );
};
