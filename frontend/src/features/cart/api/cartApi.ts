import axios, { AxiosHeaders, type AxiosResponseHeaders } from 'axios';

import { clearCartToken, getHttpErrorMessage, httpClient, persistCartToken } from '@/shared/lib/httpClient';
import { unwrapApiData } from '@/shared/lib/responseHelpers';
import { type ApiMutationResult, type ApiResponse } from '@/shared/types/api';
import { normalizeSearchText } from '@/shared/lib/searchText';

import type { Cart } from '../types/cart';
import { parseCart } from '../cartValidation';
import { isRecord } from '@/shared/lib/contractValidation';

export type CartErrorCode =
  'cart_not_found' | 'token_missing' | 'product_not_found' | 'voucher_code_missing' | 'unknown';

export class CartApiError extends Error {
  public readonly code: CartErrorCode;

  constructor(message: string, code: CartErrorCode = 'unknown') {
    super(message);
    this.name = 'CartApiError';
    this.code = code;
    Object.setPrototypeOf(this, CartApiError.prototype);
  }
}

interface CartPayload {
  cart: Cart;
}

const toNormalizedMessage = (message: string) => normalizeSearchText(message);

const inferNotFoundCode = (message: string): CartErrorCode => {
  const normalized = toNormalizedMessage(message);
  if (normalized.includes('panier')) {
    return 'cart_not_found';
  }
  if (normalized.includes('produit')) {
    return 'product_not_found';
  }
  return 'unknown';
};

const inferBadRequestCode = (message: string): CartErrorCode => {
  const normalized = toNormalizedMessage(message);
  if (normalized.includes('token')) {
    return 'token_missing';
  }
  if (normalized.includes('bon de réduction') || normalized.includes('bon')) {
    return 'voucher_code_missing';
  }
  return 'unknown';
};

const clearTokenForErrorCode = (code: CartErrorCode) => {
  if (code === 'cart_not_found' || code === 'token_missing') {
    clearCartToken();
  }
};

const toCartError = (error: unknown, fallback: string): never => {
  if (axios.isAxiosError(error)) {
    const response = error.response;
    const message = getHttpErrorMessage(error, fallback);

    if (!response) {
      throw new CartApiError(message, 'unknown');
    }

    if (response.status === 404) {
      const code = inferNotFoundCode(message);
      clearTokenForErrorCode(code);
      throw new CartApiError(message, code);
    }

    if (response.status === 400) {
      const code = inferBadRequestCode(message);
      clearTokenForErrorCode(code);
      throw new CartApiError(message, code);
    }

    throw new CartApiError(message, 'unknown');
  }

  if (error instanceof Error) {
    throw new CartApiError(error.message, 'unknown');
  }

  throw new CartApiError(fallback, 'unknown');
};

const handleCartResponse = (response: ApiResponse<CartPayload>, fallback: string) => {
  const payload = unwrapApiData(response, fallback);
  try {
    const cart = parseCart(payload.cart);
    persistCartToken(cart.token);
    return cart;
  } catch (error) {
    const message = error instanceof Error ? error.message : fallback;
    throw new CartApiError(message, 'unknown');
  }
};

const extractCartTokenFromHeaders = (headers?: AxiosResponseHeaders | Record<string, unknown>) => {
  const readHeaderValue = (key: string) => {
    if (!headers) return undefined;
    if (headers instanceof AxiosHeaders) {
      return headers.get(key);
    }

    if (isRecord(headers)) {
      return headers[key];
    }

    return undefined;
  };

  const candidates = [readHeaderValue('x-cart-token'), readHeaderValue('X-Cart-Token')];
  const tokenCandidate = candidates.find((value) => value !== undefined);

  if (typeof tokenCandidate === 'string') return tokenCandidate;
  if (Array.isArray(tokenCandidate)) {
    return tokenCandidate.find((value): value is string => typeof value === 'string') ?? null;
  }

  return null;
};

const persistHeaderCartToken = (headers?: AxiosResponseHeaders | Record<string, unknown>) => {
  const headerToken = extractCartTokenFromHeaders(headers);
  if (typeof headerToken === 'string' && headerToken !== '') {
    persistCartToken(headerToken);
  }
};

const readCartFromResponse = (
  data: ApiResponse<CartPayload>,
  headers: AxiosResponseHeaders | Record<string, unknown> | undefined,
  fallback: string,
) => {
  const cart = handleCartResponse(data, fallback);
  persistHeaderCartToken(headers);
  return cart;
};

const runCartRequest = async (
  request: () => Promise<{
    data: ApiResponse<CartPayload>;
    headers?: AxiosResponseHeaders | Record<string, unknown>;
  }>,
  fallback: string,
) => {
  try {
    const { data, headers } = await request();
    return readCartFromResponse(data, headers, fallback);
  } catch (error) {
    throw toCartError(error, fallback);
  }
};

export const fetchCart = async (): Promise<Cart> => {
  return runCartRequest(
    () => httpClient.get<ApiResponse<CartPayload>>('/api/public/cart'),
    'Impossible de charger le panier.',
  );
};

interface CartItemOptions {
  sellingType?: 'sale' | 'rental';
  currentSellingType?: 'sale' | 'rental';
  rentalMonths?: number;
  currentRentalMonths?: number;
  rentalStartDate?: string;
  currentRentalStartDate?: string;
}

export const addCartItem = async (
  productId: number,
  quantity = 1,
  options?: CartItemOptions,
): Promise<Cart> => {
  const payload: { productId: number; quantity: number; sellingType?: 'sale' | 'rental'; rentalMonths?: number; rentalStartDate?: string } = {
    productId,
    quantity,
  };
  if (options?.sellingType) {
    payload.sellingType = options.sellingType;
  }
  if (options?.rentalMonths !== undefined) {
    payload.rentalMonths = options.rentalMonths;
  }
  if (options?.rentalStartDate) {
    payload.rentalStartDate = options.rentalStartDate;
  }

  return runCartRequest(
    () => httpClient.post<ApiResponse<CartPayload>>('/api/public/cart/items', payload),
    "Impossible d'ajouter le produit au panier.",
  );
};

const buildRentalParams = (options?: CartItemOptions) => {
  const months = options?.currentRentalMonths ?? options?.rentalMonths;
  const startDate = options?.currentRentalStartDate ?? options?.rentalStartDate;
  const sellingType = options?.currentSellingType ?? options?.sellingType;
  const params: Record<string, number | string> = {};
  if (sellingType) {
    params.currentSellingType = sellingType;
  }
  if (typeof months === 'number') {
    params.currentRentalMonths = months;
  }
  if (typeof startDate === 'string' && startDate.trim() !== '') {
    params.currentRentalStartDate = startDate;
  }

  return Object.keys(params).length > 0 ? params : undefined;
};

export const removeCartItem = async (
  productId: number,
  options?: CartItemOptions,
): Promise<Cart> => {
  return runCartRequest(
    () => httpClient.delete<ApiResponse<CartPayload>>(
      `/api/public/cart/items/${productId}`,
      {
        params: buildRentalParams(options),
      },
    ),
    'Impossible de retirer le produit du panier.',
  );
};

export const updateCartItemQuantity = async (
  productId: number,
  quantity: number,
  options?: CartItemOptions,
): Promise<Cart> => {
  const payload: { quantity: number; sellingType?: 'sale' | 'rental'; currentSellingType?: 'sale' | 'rental'; rentalMonths?: number; currentRentalMonths?: number; rentalStartDate?: string; currentRentalStartDate?: string } = {
    quantity,
  };
  if (options?.sellingType) {
    payload.sellingType = options.sellingType;
  }
  if (options?.currentSellingType) {
    payload.currentSellingType = options.currentSellingType;
  }
  if (options?.rentalMonths !== undefined) {
    payload.rentalMonths = options.rentalMonths;
  }
  if (options?.currentRentalMonths !== undefined) {
    payload.currentRentalMonths = options.currentRentalMonths;
  }
  if (options?.rentalStartDate) {
    payload.rentalStartDate = options.rentalStartDate;
  }
  if (options?.currentRentalStartDate) {
    payload.currentRentalStartDate = options.currentRentalStartDate;
  }

  return runCartRequest(
    () => httpClient.patch<ApiResponse<CartPayload>>(`/api/public/cart/items/${productId}`, payload),
    'Impossible de mettre à jour la quantité.',
  );
};

export const clearCart = async (): Promise<ApiMutationResult<Cart>> => {
  try {
    const { data, headers } = await httpClient.delete<ApiResponse<CartPayload>>('/api/public/cart');
    const cart = readCartFromResponse(data, headers, 'Impossible de vider le panier.');
    return { data: cart, message: data.message };
  } catch (error) {
    throw toCartError(error, 'Impossible de vider le panier.');
  }
};

export const applyVoucherCode = async (voucherCode: string): Promise<Cart> => {
  return runCartRequest(
    () => httpClient.post<ApiResponse<CartPayload>>('/api/public/cart/voucher-code', { voucherCode }),
    "Impossible d'appliquer le bon de réduction.",
  );
};

export const clearVoucherCode = async (cartToken?: string): Promise<Cart> => {
  return runCartRequest(
    () => httpClient.delete<ApiResponse<CartPayload>>(
      '/api/public/cart/voucher-code',
      {
        params: cartToken ? { cartToken } : undefined,
      },
    ),
    'Impossible de supprimer le bon de réduction.',
  );
};
