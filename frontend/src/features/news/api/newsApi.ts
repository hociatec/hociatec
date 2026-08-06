import { isAxiosError } from 'axios';

import { getHttpErrorMessage, httpClient, requestSignalConfig } from '@/shared/lib/httpClient';
import { unwrapApiData } from '@/shared/lib/apiResponses';
import { type ApiResponse } from '@/shared/types/api';

export class NewsApiError extends Error {
  readonly statusCode: number | undefined;

  constructor(message: string, statusCode?: number) {
    super(message);
    this.name = 'NewsApiError';
    this.statusCode = statusCode;
  }
}

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

  return unwrapApiData(data, 'Impossible de charger les actualités.');
};

export const fetchNewsArticle = async (
  slug: string,
  options: RequestOptions = {},
): Promise<NewsArticleDto> => {
  const { data } = await httpClient.get<ApiResponse<{ article: NewsArticleDto }>>(
    `/api/public/news/${encodeURIComponent(slug)}`,
    requestSignalConfig(options.signal),
  );

  return unwrapApiData(data, 'Impossible de charger l’actualité.').article;
};

export const shareNewsArticleByEmail = async (
  slug: string,
  payload: { email: string },
): Promise<{ sent: boolean; to: string; message: string }> => {
  try {
    const { data } = await httpClient.post<
      ApiResponse<{ sent: boolean; to: string; message: string }>
    >(`/api/public/news/${encodeURIComponent(slug)}/share`, payload);

    return unwrapApiData(data, "Impossible d'envoyer l’actualité par e-mail.");
  } catch (error) {
    if (error instanceof NewsApiError) throw error;
    if (isAxiosError(error)) {
      throw new NewsApiError(
        getHttpErrorMessage(error, "Impossible d'envoyer l’actualité par e-mail."),
        error.response?.status,
      );
    }

    if (error instanceof Error) {
      throw new NewsApiError(error.message);
    }

    throw error;
  }
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

  return unwrapApiData(data, 'Impossible de charger les commentaires.');
};

export const createNewsComment = async (
  slug: string,
  content: string,
): Promise<NewsCommentDto> => {
  const { data } = await httpClient.post<ApiResponse<{ comment: NewsCommentDto }>>(
    `/api/public/news/${encodeURIComponent(slug)}/comments`,
    { content },
  );

  return unwrapApiData(data, 'Impossible de publier le commentaire.').comment;
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
  >('/api/admin/news', { params: { page, perPage: 10, q: q?.trim() || undefined } });

  return unwrapApiData(data, 'Impossible de charger les actualités.');
};

export const fetchAdminNewsArticle = async (id: number): Promise<NewsArticleDto> => {
  const { data } = await httpClient.get<ApiResponse<{ article: NewsArticleDto }>>(
    `/api/admin/news/${id}`,
  );
  return unwrapApiData(data, 'Actualité introuvable.').article;
};

export const createAdminNewsArticle = async (payload: NewsArticlePayload): Promise<NewsArticleDto> => {
  const { data } = await httpClient.post<ApiResponse<{ article: NewsArticleDto }>>(
    '/api/admin/news',
    payload,
  );
  return unwrapApiData(data, 'Impossible de créer l’actualité.').article;
};

export const updateAdminNewsArticle = async (
  id: number,
  payload: NewsArticlePayload,
): Promise<NewsArticleDto> => {
  const { data } = await httpClient.put<ApiResponse<{ article: NewsArticleDto }>>(
    `/api/admin/news/${id}`,
    payload,
  );
  return unwrapApiData(data, 'Impossible de modifier l’actualité.').article;
};

export const deleteAdminNewsArticle = async (id: number): Promise<void> => {
  const { data } = await httpClient.delete<ApiResponse<{ deleted: boolean }>>(`/api/admin/news/${id}`);
  unwrapApiData(data, 'Impossible de supprimer l’actualité.');
};

export const sendAdminNewsArticleEmail = async (id: number): Promise<void> => {
  const { data } = await httpClient.post<ApiResponse<{ sent: boolean }>>(
    `/api/admin/news/${id}/send-email`,
  );
  unwrapApiData(data, 'Impossible de planifier l’envoi.');
};

export const deleteAdminNewsComment = async (id: number): Promise<void> => {
  const { data } = await httpClient.delete<ApiResponse<{ deleted: boolean }>>(
    `/api/admin/news/comments/${id}`,
  );
  unwrapApiData(data, 'Impossible de supprimer le commentaire.');
};
