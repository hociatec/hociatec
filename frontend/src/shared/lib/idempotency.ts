import type { AxiosRequestConfig } from 'axios';

import { IDEMPOTENCY_HEADER_NAME } from './http/headers';
import { normalizeSearchText } from '@/shared/lib/searchText';
import { isRecord } from './contractValidation';

const normalizeValue = (value: unknown): unknown => {
  if (Array.isArray(value)) return value.map(normalizeValue);

  if (isRecord(value)) {
    return Object.fromEntries(
      Object.entries(value)
        .sort(([left], [right]) => left.localeCompare(right))
        .map(([key, entry]) => [key, normalizeValue(entry)]),
    );
  }

  return value;
};

const hashString = (value: string) => {
  let hash = 5381;

  for (let index = 0; index < value.length; index += 1) {
    hash = (hash * 33) ^ value.charCodeAt(index);
  }

  return (hash >>> 0).toString(36);
};

export const createBusinessIdempotencyKey = (scope: string, payload: unknown) => {
  const normalizedScope = normalizeSearchText(scope).replace(/[^a-z0-9_.:-]/gi, '-');
  const payloadHash = hashString(JSON.stringify(normalizeValue(payload)));

  return `${normalizedScope}:${payloadHash}`;
};

export const idempotencyRequestConfig = (
  scope: string,
  payload: unknown,
): Pick<AxiosRequestConfig, 'headers'> => ({
  headers: {
    [IDEMPOTENCY_HEADER_NAME]: createBusinessIdempotencyKey(scope, payload),
  },
});
