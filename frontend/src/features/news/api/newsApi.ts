import { httpClient, requestSignalConfig } from '@/shared/lib/httpClient';
import { isApiOk, type ApiResponse } from '@/shared/types/api';

export interface NewsArticleDto {
  id: number;
  title: string;
  slug: string;
  excerpt: string;
  content: string;
  category: string | null;
  isPublished: boolean;
  viewsCount: number;
  publishedAt: string | null;
  createdAt: string;
  updatedAt: string;
}

export interface NewsCommentDto {
  id: number;
  content: string;
  createdAt: string;
  author: {
    id: number;
    name: string;
  };
}

export interface PaginationMeta {
  page: number;
  perPage: number;
  total: number;
  totalPages: number;
}

type RequestOptions = {
  signal?: AbortSignal;
};

export const fetchNewsArticles = async ({
  page = 1,
  perPage = 9,
  q,
  signal,
}: {
  page?: number;
  perPage?: number;
  q?: string;
} & RequestOptions = {}): Promise<{ items: NewsArticleDto[]; meta: PaginationMeta }> => {
  const { data } = await httpClient.get<
    ApiResponse<{ items: NewsArticleDto[]; meta: PaginationMeta }>
  >('/api/public/news', {
    params: { page, perPage, ...(q?.trim() ? { q: q.trim() } : {}) },
    ...requestSignalConfig(signal),
  });

  if (isApiOk(data)) return data.data;
  throw new Error(data.status === 'error' ? data.message : 'Impossible de charger les actualités.');
};

export const fetchNewsArticle = async (
  slug: string,
  options: RequestOptions = {},
): Promise<NewsArticleDto> => {
  const { data } = await httpClient.get<ApiResponse<{ article: NewsArticleDto }>>(
    `/api/public/news/${encodeURIComponent(slug)}`,
    requestSignalConfig(options.signal),
  );

  if (isApiOk(data)) return data.data.article;
  throw new Error(data.status === 'error' ? data.message : 'Impossible de charger l’actualité.');
};

export const fetchNewsComments = async (
  slug: string,
  page = 1,
  options: RequestOptions = {},
): Promise<{ items: NewsCommentDto[]; meta: PaginationMeta }> => {
  const { data } = await httpClient.get<
    ApiResponse<{ items: NewsCommentDto[]; meta: PaginationMeta }>
  >(`/api/public/news/${encodeURIComponent(slug)}/comments`, {
    params: { page, perPage: 10 },
    ...requestSignalConfig(options.signal),
  });

  if (isApiOk(data)) return data.data;
  throw new Error(data.status === 'error' ? data.message : 'Impossible de charger les commentaires.');
};

export const createNewsComment = async (
  slug: string,
  content: string,
): Promise<NewsCommentDto> => {
  const { data } = await httpClient.post<ApiResponse<{ comment: NewsCommentDto }>>(
    `/api/public/news/${encodeURIComponent(slug)}/comments`,
    { content },
  );

  if (isApiOk(data)) return data.data.comment;
  throw new Error(data.status === 'error' ? data.message : 'Impossible de publier le commentaire.');
};

export interface NewsArticlePayload {
  title: string;
  slug: string;
  excerpt: string;
  content: string;
  category?: string | null;
  isPublished: boolean;
  publishedAt?: string | null;
}

export const fetchAdminNewsArticles = async ({
  page = 1,
  q,
}: {
  page?: number;
  q?: string;
} = {}): Promise<{ items: NewsArticleDto[]; meta: PaginationMeta }> => {
  const { data } = await httpClient.get<
    ApiResponse<{ items: NewsArticleDto[]; meta: PaginationMeta }>
  >('/api/admin/news', { params: { page, perPage: 20, q: q?.trim() || undefined } });

  if (isApiOk(data)) return data.data;
  throw new Error(data.status === 'error' ? data.message : 'Impossible de charger les actualités.');
};

export const fetchAdminNewsArticle = async (id: number): Promise<NewsArticleDto> => {
  const { data } = await httpClient.get<ApiResponse<{ article: NewsArticleDto }>>(
    `/api/admin/news/${id}`,
  );
  if (isApiOk(data)) return data.data.article;
  throw new Error(data.status === 'error' ? data.message : 'Actualité introuvable.');
};

export const createAdminNewsArticle = async (payload: NewsArticlePayload): Promise<NewsArticleDto> => {
  const { data } = await httpClient.post<ApiResponse<{ article: NewsArticleDto }>>(
    '/api/admin/news',
    payload,
  );
  if (isApiOk(data)) return data.data.article;
  throw new Error(data.status === 'error' ? data.message : 'Impossible de créer l’actualité.');
};

export const updateAdminNewsArticle = async (
  id: number,
  payload: NewsArticlePayload,
): Promise<NewsArticleDto> => {
  const { data } = await httpClient.put<ApiResponse<{ article: NewsArticleDto }>>(
    `/api/admin/news/${id}`,
    payload,
  );
  if (isApiOk(data)) return data.data.article;
  throw new Error(data.status === 'error' ? data.message : 'Impossible de modifier l’actualité.');
};

export const deleteAdminNewsArticle = async (id: number): Promise<void> => {
  const { data } = await httpClient.delete<ApiResponse<{ deleted: boolean }>>(`/api/admin/news/${id}`);
  if (!isApiOk(data)) throw new Error(data.status === 'error' ? data.message : 'Impossible de supprimer l’actualité.');
};

export const sendAdminNewsArticleEmail = async (id: number): Promise<void> => {
  const { data } = await httpClient.post<ApiResponse<{ sent: boolean }>>(
    `/api/admin/news/${id}/send-email`,
  );
  if (!isApiOk(data)) throw new Error(data.status === 'error' ? data.message : 'Impossible de planifier l’envoi.');
};

export const deleteAdminNewsComment = async (id: number): Promise<void> => {
  const { data } = await httpClient.delete<ApiResponse<{ deleted: boolean }>>(
    `/api/admin/news/comments/${id}`,
  );
  if (!isApiOk(data)) throw new Error(data.status === 'error' ? data.message : 'Impossible de supprimer le commentaire.');
};
