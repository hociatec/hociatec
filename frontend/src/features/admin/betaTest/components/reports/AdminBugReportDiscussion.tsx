import type { BugReportCommentDto, PaginationMeta } from '../../api';
import { formatOptionalFrenchDateTime } from '@/shared/lib/formatters';
import { clampAtLeast, clampWithin } from '@/shared/lib/number';

export const AdminBugReportDiscussion = ({
  commentPage,
  comments,
  commentsMeta,
  loadingComments,
  newCommentText,
  postCommentPending,
  onCommentPageChange,
  onNewCommentTextChange,
  onPostComment,
}: {
  commentPage: number;
  comments: BugReportCommentDto[];
  commentsMeta: PaginationMeta | null;
  loadingComments: boolean;
  newCommentText: string;
  postCommentPending: boolean;
  onCommentPageChange: (updater: (page: number) => number) => void;
  onNewCommentTextChange: (value: string) => void;
  onPostComment: () => void;
}) => (
  <>
    <div className="border-b border-stone-200 p-4">
      <h2 className="font-semibold text-brand-900">Discussion</h2>
    </div>
    <div className="flex-1 space-y-3 overflow-y-auto bg-stone-50 p-4">
      {loadingComments ? <p className="sr-only">Chargement des messages...</p> : comments.length === 0 ? <p className="text-sm text-stone-500">Aucun message.</p> : comments.map((comment) => {
        const authorLabel = comment.author.role === 'admin' ? 'Support Hociatec' : comment.author.email;
        return <p key={comment.id} className="rounded-lg border border-stone-200 bg-white p-3 text-sm"><span className="font-semibold">{authorLabel}</span> <span className="text-stone-500">({formatOptionalFrenchDateTime(comment.createdAt)})</span> : {comment.content}</p>;
      })}
    </div>
    {commentsMeta && commentsMeta.totalPages > 1 && (
      <div className="flex items-center justify-between border-t border-stone-200 bg-white p-3 text-sm">
        <button
          type="button"
          disabled={commentPage <= 1}
          onClick={() => onCommentPageChange((value) => clampAtLeast(value - 1, 1))}
          className="rounded border border-stone-200 px-3 py-2 font-semibold disabled:opacity-50"
        >
          Précédents
        </button>
        <span>Page {commentsMeta.page} sur {commentsMeta.totalPages}</span>
        <button
          type="button"
          disabled={commentPage >= commentsMeta.totalPages}
          onClick={() => onCommentPageChange((value) => clampWithin(value + 1, 1, commentsMeta.totalPages))}
          className="rounded border border-stone-200 px-3 py-2 font-semibold disabled:opacity-50"
        >
          Suivants
        </button>
      </div>
    )}
    <form onSubmit={(event) => { event.preventDefault(); if (newCommentText.trim()) onPostComment(); }} className="flex gap-2 border-t border-stone-200 p-4">
      <input className="flex-1 rounded-lg border border-stone-300 p-3 text-sm" value={newCommentText} onChange={(event) => onNewCommentTextChange(event.target.value)} placeholder="Rédiger une réponse..." />
      <button className="rounded-lg bg-brand-700 px-4 py-3 text-sm font-semibold text-white disabled:opacity-50" disabled={postCommentPending || !newCommentText.trim()}>{postCommentPending ? 'Envoi...' : 'Répondre'}</button>
    </form>
  </>
);
