import { useEffect, useMemo, useState, type FormEvent } from 'react';
import { useNavigate, useParams } from 'react-router';
import { useMutation, useQuery, useQueryClient } from '@tanstack/react-query';

import {
  createAdminNewsArticle,
  fetchAdminNewsArticle,
  updateAdminNewsArticle,
  type NewsArticlePayload,
} from '@/features/news/publicApi';
import { PageContainer } from '@/shared/components/layout/PageContainer';
import { FeedbackMessage } from '@/shared/components/ui/page-state';
import { useDocumentTitle } from '@/shared/hooks/useDocumentTitle';
import { adminNewsQueryKeys } from '@/features/admin/news/queryKeys';

const slugify = (value: string) =>
  value.normalize('NFD').replace(/[\u0300-\u036f]/g, '').toLowerCase().replace(/[^a-z0-9]+/g, '-').replace(/^-|-$/g, '');

export const AdminNewsFormPage = () => {
  const { newsId } = useParams();
  const id = newsId ? Number(newsId) : null;
  const isEdit = Number.isFinite(id);
  const navigate = useNavigate();
  const queryClient = useQueryClient();
  useDocumentTitle(isEdit ? 'Admin - Modifier une actualité' : 'Admin - Nouvelle actualité');
  const [payload, setPayload] = useState<NewsArticlePayload>({
    title: '',
    slug: '',
    excerpt: '',
    content: '',
    category: 'Vie de l’entreprise',
    isPublished: true,
    publishedAt: null,
  });
  const [error, setError] = useState<string | null>(null);
  const articleQuery = useQuery({
    queryKey: adminNewsQueryKeys.detail(isEdit ? id : null),
    queryFn: () => fetchAdminNewsArticle(id ?? 0),
    enabled: isEdit && Boolean(id),
  });
  const saveMutation = useMutation({
    mutationFn: (nextPayload: NewsArticlePayload) =>
      isEdit && id
        ? updateAdminNewsArticle(id, nextPayload)
        : createAdminNewsArticle(nextPayload),
    onSuccess: () => {
      void queryClient.invalidateQueries({ queryKey: ['admin', 'news'] });
      navigate('/admin/news');
    },
    onError: (reason) =>
      setError(reason instanceof Error ? reason.message : 'Enregistrement impossible.'),
  });

  useEffect(() => {
    if (!articleQuery.data) return;
    const article = articleQuery.data;
      setPayload({
        title: article.title,
        slug: article.slug,
        excerpt: article.excerpt,
        content: article.content,
        category: article.category,
        isPublished: article.isPublished,
        publishedAt: article.publishedAt,
      });
  }, [articleQuery.data]);

  const canSave = useMemo(() => payload.title.trim() && payload.slug.trim() && payload.excerpt.trim() && payload.content.trim(), [payload]);

  const setField = <K extends keyof NewsArticlePayload>(key: K, value: NewsArticlePayload[K]) =>
    setPayload((current) => ({ ...current, [key]: value }));

  const handleSubmit = async (event: FormEvent<HTMLFormElement>) => {
    event.preventDefault();
    if (!canSave) return;
    setError(null);
    saveMutation.mutate(payload);
  };

  return (
    <PageContainer size="admin" title={isEdit ? 'Modifier une actualité' : 'Nouvelle actualité'}>
      {error || articleQuery.error ? (
        <FeedbackMessage>
          {error ??
            (articleQuery.error instanceof Error
              ? articleQuery.error.message
              : 'Chargement impossible.')}
        </FeedbackMessage>
      ) : null}
      <form onSubmit={handleSubmit} className="grid gap-5 rounded-2xl border border-brand-100 bg-white p-6 shadow-sm">
        <label className="grid gap-2 text-sm font-semibold text-brand-900">
          Titre
          <input className="rounded-xl border border-brand-200 p-3" value={payload.title} onChange={(e) => setPayload((current) => ({ ...current, title: e.target.value, slug: current.slug || slugify(e.target.value) }))} />
        </label>
        <label className="grid gap-2 text-sm font-semibold text-brand-900">
          Slug
          <input className="rounded-xl border border-brand-200 p-3" value={payload.slug} onChange={(e) => setField('slug', slugify(e.target.value))} />
        </label>
        <label className="grid gap-2 text-sm font-semibold text-brand-900">
          Catégorie
          <input className="rounded-xl border border-brand-200 p-3" value={payload.category ?? ''} onChange={(e) => setField('category', e.target.value)} />
        </label>
        <label className="grid gap-2 text-sm font-semibold text-brand-900">
          Résumé
          <textarea className="rounded-xl border border-brand-200 p-3" rows={4} value={payload.excerpt} onChange={(e) => setField('excerpt', e.target.value)} />
        </label>
        <label className="grid gap-2 text-sm font-semibold text-brand-900">
          Contenu
          <textarea className="rounded-xl border border-brand-200 p-3" rows={12} value={payload.content} onChange={(e) => setField('content', e.target.value)} />
        </label>
        <label className="inline-flex items-center gap-3 text-sm font-semibold text-brand-900">
          <input type="checkbox" checked={payload.isPublished} onChange={(e) => setField('isPublished', e.target.checked)} />
          Publier l’actualité
        </label>
        <button type="submit" disabled={!canSave || saveMutation.isPending} className="w-fit rounded-full bg-brand-900 px-5 py-3 text-sm font-semibold text-white disabled:opacity-50">
          Enregistrer
        </button>
      </form>
    </PageContainer>
  );
};
