import { httpClient } from '@/shared/lib/httpClient';
import { type ApiResponse } from '@/shared/types/api';
import { rethrowApiError, unwrap, type StockMovementDto } from './operationsApiShared';

export const fetchStockMovements = async (): Promise<StockMovementDto[]> => {
  const { data } = await httpClient.get<ApiResponse<{ items: StockMovementDto[] }>>(
    '/api/admin/operations/stock-movements',
  );
  return unwrap(data, 'Impossible de charger les mouvements de stock').items ?? [];
};

export const createStockMovement = async (payload: {
  productId: number;
  delta: number;
  reason: string;
  note?: string;
}): Promise<StockMovementDto> => {
  const { data } = await httpClient.post<ApiResponse<{ item: StockMovementDto }>>(
    '/api/admin/operations/stock-movements',
    payload,
  );
  return unwrap(data, 'Impossible de créer le mouvement de stock').item;
};

export const updateLowStockThreshold = async (
  productId: number,
  threshold: number,
): Promise<void> => {
  try {
    const { data } = await httpClient.patch<ApiResponse<{ product: unknown }>>(
      `/api/admin/operations/products/${productId}/low-stock-threshold`,
      { threshold },
    );
    unwrap(data, 'Impossible de modifier le seuil de stock');
  } catch (error) {
    rethrowApiError(error, 'Impossible de modifier le seuil de stock');
  }
};
