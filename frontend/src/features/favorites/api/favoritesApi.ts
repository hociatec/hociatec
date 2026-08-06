import { httpClient } from '@/shared/lib/httpClient';
import { unwrapApiData } from '@/shared/lib/apiResponses';
import { type PaginatedResult, type PaginationMeta } from '@/shared/types/api';
import type { CatalogProduct } from '@/features/catalog/publicApi';

export interface FavoriteDto {
  addedAt: string;
  product: CatalogProduct;
}

export interface FavoriteResponse {
  favorite: FavoriteDto;
  alreadyFavorite: boolean;
}

export const fetchFavorites = async (): Promise<FavoriteDto[]> => {
  const { data } = await httpClient.get<ApiResponse<{ items: FavoriteDto[] }>>('/api/favorites', {
    params: { page: 1, perPage: 50 },
  });

  const payload = unwrapApiData(data, 'Impossible de charger vos favoris.');
  return payload.items;
};

export const fetchFavoritesPage = async (
  page = 1,
  perPage = 10,
): Promise<PaginatedResult<FavoriteDto>> => {
  const { data } = await httpClient.get<ApiResponse<{ items: FavoriteDto[]; meta: PaginationMeta }>>(
    '/api/favorites',
    { params: { page, perPage } },
  );

  const payload = unwrapApiData(data, 'Impossible de charger vos favoris.');
  return { items: payload.items, meta: payload.meta };
};

export const addFavorite = async (productId: number): Promise<FavoriteResponse> => {
  const { data } = await httpClient.post<ApiResponse<FavoriteResponse>>(
    `/api/favorites/${productId}`,
  );

  const payload = unwrapApiData(data, "Impossible d'ajouter ce produit aux favoris.");
  return {
    favorite: payload.favorite,
    alreadyFavorite: Boolean(payload.alreadyFavorite),
  };
};

export const removeFavorite = async (productId: number): Promise<void> => {
  const { data } = await httpClient.delete<ApiResponse<{ removed: boolean }>>(
    `/api/favorites/${productId}`,
  );

  unwrapApiData(data, 'Impossible de retirer ce produit des favoris.');
};
