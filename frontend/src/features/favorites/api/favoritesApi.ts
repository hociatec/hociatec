import { httpClient } from '@/shared/lib/httpClient';
import { extractApiErrorMessage } from '@/shared/lib/apiResponses';
import { isApiOk, type PaginatedResult, type PaginationMeta } from '@/shared/types/api';
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

  if (isApiOk(data)) {
    return data.data.items;
  }

  throw new Error(extractApiErrorMessage(data, 'Impossible de charger vos favoris.'));
};

export const fetchFavoritesPage = async (
  page = 1,
  perPage = 10,
): Promise<PaginatedResult<FavoriteDto>> => {
  const { data } = await httpClient.get<ApiResponse<{ items: FavoriteDto[]; meta: PaginationMeta }>>(
    '/api/favorites',
    { params: { page, perPage } },
  );

  if (isApiOk(data)) {
    return { items: data.data.items, meta: data.data.meta };
  }

  throw new Error(extractApiErrorMessage(data, 'Impossible de charger vos favoris.'));
};

export const addFavorite = async (productId: number): Promise<FavoriteResponse> => {
  const { data } = await httpClient.post<ApiResponse<FavoriteResponse>>(
    `/api/favorites/${productId}`,
  );

  if (isApiOk(data)) {
    const payload = data.data;
    return {
      favorite: payload.favorite,
      alreadyFavorite: Boolean(payload.alreadyFavorite),
    };
  }

  throw new Error(extractApiErrorMessage(data, "Impossible d'ajouter ce produit aux favoris."));
};

export const removeFavorite = async (productId: number): Promise<void> => {
  const { data } = await httpClient.delete<ApiResponse<{ removed: boolean }>>(
    `/api/favorites/${productId}`,
  );

  if (isApiOk(data)) {
    return;
  }

  throw new Error(extractApiErrorMessage(data, 'Impossible de retirer ce produit des favoris.'));
};
