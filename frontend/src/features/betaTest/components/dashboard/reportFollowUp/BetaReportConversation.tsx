import { MessageSquare } from 'lucide-react';

import { formatOptionalFrenchDateTime } from '@/shared/lib/formatters';
import type { BugReportComment, PaginationMeta } from '../../../api/betaApi';

interface BetaReportConversationProps {
  commentPage: number;
  comments: BugReportComment[];
  commentsMeta: PaginationMeta | null;
  loadingComments: boolean;
  newCommentText: string;
  sending: boolean;
  onCommentPageChange: (updater: (page: number) => number) => void;
  onCommentTextChange: (value: string) => void;
  onSubmit: (event: React.FormEvent) => void;
}

export const BetaReportConversation = ({
  commentPage,
  comments,
  commentsMeta,
  loadingComments,
  newCommentText,
  sending,
  onCommentPageChange,
  onCommentTextChange,
  onSubmit,
}: BetaReportConversationProps) => (
  <>
    <div className="flex items-center gap-2 border-b border-stone-200 bg-white px-5 py-3">
      <MessageSquare size={16} className="text-brand-700" />
      <h3 className="text-sm font-bold text-brand-900">Conversation</h3>
    </div>
    <div className="flex-1 space-y-4 overflow-y-auto bg-stone-50/50 p-4">
      {loadingComments ? (
        <p className="sr-only">Chargement des messages...</p>
      ) : comments.length === 0 ? (
        <p className="py-4 text-center text-sm text-stone-400">Pas encore de message. L'équipe technique vous répondra très bientôt ici !</p>
      ) : (
        comments.map((comment) => {
          const isAdminMessage = comment.author.role === 'admin';
          const authorLabel = isAdminMessage ? 'Support Hociatec' : 'Vous';
          const messageDate = formatOptionalFrenchDateTime(comment.createdAt);

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
  </>
);
