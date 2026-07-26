import axios, { type AxiosResponseHeaders } from 'axios';

import { clearCartToken, httpClient, persistCartToken } from '@/shared/lib/httpClient';
import { isApiOk, type ApiResponse } from '@/shared/types/api';

import type { Cart } from '../types/cart';

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

const toCartError = (error: unknown, fallback: string): never => {
  if (axios.isAxiosError(error)) {
    const response = error.response;
    const data = response?.data as ApiResponse<unknown> | undefined;
    const message = data?.status === 'error' && data.message ? data.message : fallback;

    if (!response) {
      throw new CartApiError(message, 'unknown');
    }

    if (response.status === 404) {
      const normalized = message.toLowerCase();
      const code = normalized.includes('panier')
        ? 'cart_not_found'
        : normalized.includes('produit')
          ? 'product_not_found'
          : 'unknown';
      if (code === 'cart_not_found') {
        clearCartToken();
      }
      throw new CartApiError(message, code);
    }

    if (response.status === 400) {
      const normalized = message.toLowerCase();
      const code = normalized.includes('token')
        ? 'token_missing'
        : normalized.includes('bon de réduction') || normalized.includes('bon')
          ? 'voucher_code_missing'
          : 'unknown';
      if (code === 'token_missing') {
        clearCartToken();
      }
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
  if (isApiOk(response)) {
    const cart = response.data.cart;
    persistCartToken(cart.token);
    return cart;
  }

  const message = response.status === 'error' ? response.message : fallback;
  throw new CartApiError(message, 'unknown');
};

const extractCartTokenFromHeaders = (headers?: AxiosResponseHeaders | Record<string, unknown>) => {
  if (!headers) {
    return null;
  }

  if (typeof (headers as AxiosResponseHeaders).get === 'function') {
    const value = (headers as AxiosResponseHeaders).get('x-cart-token');
    if (typeof value === 'string') {
      return value;
    }
    if (Array.isArray(value)) {
      return value.find((item): item is string => typeof item === 'string') ?? null;
    }
  }

  const lowerValue = (headers as Record<string, unknown>)['x-cart-token'];
  const upperValue = (headers as Record<string, unknown>)['X-Cart-Token'];

  const pick = [lowerValue, upperValue].find((value): value is unknown => value !== undefined);

  if (typeof pick === 'string') {
    return pick;
  }

  if (Array.isArray(pick)) {
    return pick.find((item): item is string => typeof item === 'string') ?? null;
  }

  return null;
};

export const fetchCart = async (): Promise<Cart> => {
  try {
    const { data, headers } = await httpClient.get<ApiResponse<CartPayload>>('/api/public/cart');
    const cart = handleCartResponse(data, 'Impossible de charger le panier.');
    const headerToken = extractCartTokenFromHeaders(headers);
    if (typeof headerToken === 'string' && headerToken !== '') {
      persistCartToken(headerToken);
    }
    return cart;
  } catch (error) {
    throw toCartError(error, 'Impossible de charger le panier.');
  }
};

interface CartItemOptions {
  rentalMonths?: number;
  currentRentalMonths?: number;
}

export const addCartItem = async (
  productId: number,
  quantity = 1,
  options?: CartItemOptions,
): Promise<Cart> => {
  try {
    const payload: { productId: number; quantity: number; rentalMonths?: number } = {
      productId,
      quantity,
    };
    if (options?.rentalMonths !== undefined) {
      payload.rentalMonths = options.rentalMonths;
    }
    const { data, headers } = await httpClient.post<ApiResponse<CartPayload>>(
      '/api/public/cart/items',
      payload,
    );
    const cart = handleCartResponse(data, "Impossible d'ajouter le produit au panier.");
    const headerToken = extractCartTokenFromHeaders(headers);
    if (typeof headerToken === 'string' && headerToken !== '') {
      persistCartToken(headerToken);
    }
    return cart;
  } catch (error) {
    throw toCartError(error, "Impossible d'ajouter le produit au panier.");
  }
};

const buildRentalParams = (options?: CartItemOptions) => {
  const params: Record<string, number> = {};
  const months = options?.currentRentalMonths ?? options?.rentalMonths;
  if (typeof months === 'number') {
    params.currentRentalMonths = months;
  }
  return Object.keys(params).length > 0 ? params : undefined;
};

export const removeCartItem = async (
  productId: number,
  options?: CartItemOptions,
): Promise<Cart> => {
  try {
    const { data, headers } = await httpClient.delete<ApiResponse<CartPayload>>(
      `/api/public/cart/items/${productId}`,
      {
        params: buildRentalParams(options),
      },
    );
    const cart = handleCartResponse(data, 'Impossible de retirer le produit du panier.');
    const headerToken = extractCartTokenFromHeaders(headers);
    if (typeof headerToken === 'string' && headerToken !== '') {
      persistCartToken(headerToken);
    }
    return cart;
  } catch (error) {
    throw toCartError(error, 'Impossible de retirer le produit du panier.');
  }
};

export const updateCartItemQuantity = async (
  productId: number,
  quantity: number,
  options?: CartItemOptions,
): Promise<Cart> => {
  try {
    const payload: { quantity: number; rentalMonths?: number; currentRentalMonths?: number } = {
      quantity,
    };
    if (options?.rentalMonths !== undefined) {
      payload.rentalMonths = options.rentalMonths;
    }
    if (options?.currentRentalMonths !== undefined) {
      payload.currentRentalMonths = options.currentRentalMonths;
    }
    const { data, headers } = await httpClient.patch<ApiResponse<CartPayload>>(
      `/api/public/cart/items/${productId}`,
      payload,
    );
    const cart = handleCartResponse(data, 'Impossible de mettre à jour la quantité.');
    const headerToken = extractCartTokenFromHeaders(headers);
    if (typeof headerToken === 'string' && headerToken !== '') {
      persistCartToken(headerToken);
    }
    return cart;
  } catch (error) {
    throw toCartError(error, 'Impossible de mettre à jour la quantité.');
  }
};

export const clearCart = async (): Promise<Cart> => {
  try {
    const { data, headers } = await httpClient.delete<ApiResponse<CartPayload>>('/api/public/cart');
    const cart = handleCartResponse(data, 'Impossible de vider le panier.');
    const headerToken = extractCartTokenFromHeaders(headers);
    if (typeof headerToken === 'string' && headerToken !== '') {
      persistCartToken(headerToken);
    }
    return cart;
  } catch (error) {
    throw toCartError(error, 'Impossible de vider le panier.');
  }
};

export const applyVoucherCode = async (voucherCode: string): Promise<Cart> => {
  try {
    const { data, headers } = await httpClient.post<ApiResponse<CartPayload>>(
      '/api/public/cart/voucher-code',
      { voucherCode },
    );
    const cart = handleCartResponse(data, "Impossible d'appliquer le bon de réduction.");
    const headerToken = extractCartTokenFromHeaders(headers);
    if (typeof headerToken === 'string' && headerToken !== '') {
      persistCartToken(headerToken);
    }
    return cart;
  } catch (error) {
    throw toCartError(error, "Impossible d'appliquer le bon de réduction.");
  }
};

export const clearVoucherCode = async (cartToken?: string): Promise<Cart> => {
  try {
    const { data, headers } = await httpClient.delete<ApiResponse<CartPayload>>(
      '/api/public/cart/voucher-code',
      {
        params: cartToken ? { cartToken } : undefined,
      },
    );
    const cart = handleCartResponse(data, 'Impossible de supprimer le bon de réduction.');
    const headerToken = extractCartTokenFromHeaders(headers);
    if (typeof headerToken === 'string' && headerToken !== '') {
      persistCartToken(headerToken);
    }
    return cart;
  } catch (error) {
    throw toCartError(error, 'Impossible de supprimer le bon de réduction.');
  }
};
