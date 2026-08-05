import { useEffect, useState, type FormEvent } from 'react';
import { Link } from 'react-router';

import {
  createNewsComment,
  deleteAdminNewsComment,
  fetchNewsComments,
  type NewsCommentDto,
  type PaginationMeta,
} from '@/features/news/api/newsApi';
import { useAuth } from '@/features/auth/hooks/useAuth';
import { hasPermission } from '@/features/auth/lib/permissions';
import { ErrorState, LoadingState } from '@/shared/components/ui/page-state';
import { formatFrenchDateTime } from '@/shared/lib/formatters';

export const NewsComments = ({ slug }: { slug: string }) => {
  const { user } = useAuth();
  const isAdmin = hasPermission(user, 'news.comments.moderate');
  const [comments, setComments] = useState<NewsCommentDto[]>([]);
  const [meta, setMeta] = useState<PaginationMeta | null>(null);
  const [page, setPage] = useState(1);
  const [content, setContent] = useState('');
  const [loading, setLoading] = useState(true);
  const [error, setError] = useState<string | null>(null);
  const [submitting, setSubmitting] = useState(false);

  useEffect(() => {
    let cancelled = false;
    const controller = new AbortController();
    setLoading(true);
    setError(null);
    void fetchNewsComments(slug, page, { signal: controller.signal })
      .then((result) => {
        if (cancelled) return;
        setComments(result.items);
        setMeta(result.meta);
      })
      .catch((reason) => {
        if (controller.signal.aborted) return;
        if (!cancelled) setError(reason instanceof Error ? reason.message : 'Erreur de chargement.');
      })
      .finally(() => {
        if (!cancelled) setLoading(false);
      });

    return () => {
      cancelled = true;
      controller.abort();
    };
  }, [page, slug]);

  const handleSubmit = async (event: FormEvent<HTMLFormElement>) => {
    event.preventDefault();
    const nextContent = content.trim();
    if (nextContent.length < 3) return;
    setSubmitting(true);
    try {
      const comment = await createNewsComment(slug, nextContent);
      setContent('');
      setPage(1);
      setComments((current) => [comment, ...current]);
      setMeta((current) =>
        current ? { ...current, total: current.total + 1, totalPages: Math.max(1, current.totalPages) } : current,
      );
    } finally {
      setSubmitting(false);
    }
  };

  const handleDelete = async (comment: NewsCommentDto) => {
    if (!window.confirm('Supprimer ce commentaire ?')) return;
    await deleteAdminNewsComment(comment.id);
    setComments((current) => current.filter((item) => item.id !== comment.id));
    setMeta((current) => (current ? { ...current, total: Math.max(0, current.total - 1) } : current));
  };

  return (
    <section className="rounded-2xl border border-brand-100 bg-white p-6 shadow-sm" aria-labelledby="news-comments-title">
      <h2 id="news-comments-title" className="text-2xl font-semibold text-brand-900">
        Commentaires
      </h2>

      {loading ? (
        <LoadingState>Chargement des commentaires...</LoadingState>
      ) : error ? (
        <ErrorState>{error}</ErrorState>
      ) : comments.length === 0 ? (
        <p className="mt-6 text-sm text-stone-500">Aucun commentaire pour le moment.</p>
      ) : (
        <div className="mt-6 grid gap-4">
          {comments.map((comment) => (
            <article key={comment.id} className="rounded-2xl border border-brand-100 bg-brand-50 p-4">
              <p className="text-sm font-semibold text-brand-900">
                {comment.author.name} ({formatFrenchDateTime(comment.createdAt)}) :
              </p>
              <p className="mt-2 whitespace-pre-line text-sm leading-6 text-stone-700">{comment.content}</p>
              {isAdmin ? (
                <button
                  type="button"
                  onClick={() => void handleDelete(comment)}
                  className="mt-3 text-sm font-semibold text-red-700 underline"
                >
                  Supprimer le commentaire
                </button>
              ) : null}
            </article>
          ))}
        </div>
      )}

      {meta && meta.totalPages > 1 ? (
        <div className="mt-6 flex justify-center gap-3">
          <button
            type="button"
            disabled={page <= 1}
            onClick={() => setPage((current) => current - 1)}
            className="rounded-full border border-brand-200 px-4 py-2 text-sm font-semibold disabled:opacity-50"
          >
            Commentaires précédents
          </button>
          <button
            type="button"
            disabled={page >= meta.totalPages}
            onClick={() => setPage((current) => current + 1)}
            className="rounded-full border border-brand-200 px-4 py-2 text-sm font-semibold disabled:opacity-50"
          >
            Commentaires suivants
          </button>
        </div>
      ) : null}

      {user ? (
        <form onSubmit={handleSubmit} className="mt-8 grid gap-3 border-t border-brand-100 pt-6">
          <label htmlFor="news-comment" className="font-semibold text-brand-900">
            Ajouter un commentaire
          </label>
          <textarea
            id="news-comment"
            value={content}
            onChange={(event) => setContent(event.target.value)}
            rows={4}
            maxLength={1200}
            className="rounded-2xl border border-brand-200 p-4 text-sm outline-none focus:border-brand-500 focus:ring-2 focus:ring-brand-100"
          />
          <button
            type="submit"
            disabled={submitting || content.trim().length < 3}
            className="w-fit rounded-full bg-brand-900 px-5 py-2 text-sm font-semibold text-white disabled:opacity-50"
          >
            Publier le commentaire
          </button>
        </form>
      ) : (
        <p className="mt-8 border-t border-brand-100 pt-6 text-sm text-stone-600">
          <Link to="/login" className="font-semibold text-brand-700 underline">
            Connectez-vous
          </Link>{' '}
          pour ajouter un commentaire.
        </p>
      )}
    </section>
  );
};
