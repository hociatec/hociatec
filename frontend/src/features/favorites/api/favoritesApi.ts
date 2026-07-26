import { httpClient } from '@/shared/lib/httpClient';
import { isApiOk, type ApiResponse } from '@/shared/types/api';
import type { CatalogProduct } from '@/features/catalog/api';

export interface FavoriteDto {
  addedAt: string;
  product: CatalogProduct;
}

export interface FavoriteResponse {
  favorite: FavoriteDto;
  alreadyFavorite: boolean;
}

const buildErrorMessage = (response: ApiResponse<unknown>, fallback: string) =>
  response.status === 'error' ? response.message : fallback;

export const fetchFavorites = async (): Promise<FavoriteDto[]> => {
  const { data } = await httpClient.get<ApiResponse<{ items: FavoriteDto[] }>>('/api/favorites');

  if (isApiOk(data)) {
    return data.data.items;
  }

  throw new Error(buildErrorMessage(data, 'Impossible de charger vos favoris.'));
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

  throw new Error(buildErrorMessage(data, 'Impossible d\'ajouter ce produit aux favoris.'));
};

export const removeFavorite = async (productId: number): Promise<void> => {
  const { data } = await httpClient.delete<ApiResponse<{ removed: boolean }>>(
    `/api/favorites/${productId}`,
  );

  if (isApiOk(data)) {
    return;
  }

  throw new Error(buildErrorMessage(data, 'Impossible de retirer ce produit des favoris.'));
};
