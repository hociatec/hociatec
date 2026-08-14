import { httpClient } from '@/shared/lib/httpClient';
import { unwrapApiData } from '@/shared/lib/responseHelpers';
import { type ApiResponse, type PaginatedResult, type PaginationMeta } from '@/shared/types/api';
import type { CatalogProduct } from '@/features/catalog/publicApi';
import type { QuoteServiceDto } from '@/features/quotes/publicApi';
import type { NewsArticleDto } from '@/features/news/publicApi';

export type FavoriteCategory = 'product' | 'service' | 'news' | 'podcast';

export interface FavoriteDto {
  category: FavoriteCategory;
  targetId: number;
  addedAt: string;
  product?: CatalogProduct | null;
  service?: QuoteServiceDto | null;
  article?: NewsArticleDto | null;
  podcast?: null;
}

export interface FavoriteResponse {
  favorite: FavoriteDto | null;
  alreadyFavorite: boolean;
}

export interface FavoriteStatusResponse {
  category: FavoriteCategory;
  targetId: number;
  isFavorite: boolean;
}

export const fetchFavorites = async (category?: FavoriteCategory | 'all'): Promise<FavoriteDto[]> => {
  const { data } = await httpClient.get<ApiResponse<{ items: FavoriteDto[] }>>('/api/favorites', {
    params: { page: 1, perPage: 50, ...(category && category !== 'all' ? { category } : {}) },
  });

  const payload = unwrapApiData(data, 'Impossible de charger vos favoris.');
  return payload.items;
};

export const fetchFavoritesPage = async (
  page = 1,
  perPage = 10,
  category?: FavoriteCategory | 'all',
): Promise<PaginatedResult<FavoriteDto>> => {
  const { data } = await httpClient.get<ApiResponse<{ items: FavoriteDto[]; meta: PaginationMeta }>>(
    '/api/favorites',
    { params: { page, perPage, ...(category && category !== 'all' ? { category } : {}) } },
  );

  const payload = unwrapApiData(data, 'Impossible de charger vos favoris.');
  return { items: payload.items, meta: payload.meta };
};

export const fetchFavoriteStatus = async (
  category: FavoriteCategory,
  targetId: number,
): Promise<FavoriteStatusResponse> => {
  const { data } = await httpClient.get<ApiResponse<FavoriteStatusResponse>>(
    `/api/favorites/${category}/${targetId}/status`,
  );

  return unwrapApiData(data, 'Impossible de verifier ce favori.');
};

export const addFavoriteItem = async (
  category: FavoriteCategory,
  targetId: number,
): Promise<FavoriteResponse> => {
  const { data } = await httpClient.post<ApiResponse<FavoriteResponse>>(
    `/api/favorites/${category}/${targetId}`,
  );

  const payload = unwrapApiData(data, "Impossible d'ajouter cet element aux favoris.");
  return {
    favorite: payload.favorite,
    alreadyFavorite: Boolean(payload.alreadyFavorite),
  };
};

export const removeFavoriteItem = async (
  category: FavoriteCategory,
  targetId: number,
): Promise<void> => {
  const { data } = await httpClient.delete<ApiResponse<{ removed: boolean }>>(
    `/api/favorites/${category}/${targetId}`,
  );

  unwrapApiData(data, 'Impossible de retirer cet element des favoris.');
};
