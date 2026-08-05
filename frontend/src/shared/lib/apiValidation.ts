export class ApiContractError extends Error {
  constructor(message = 'Réponse API invalide.') {
    super(message);
    this.name = 'ApiContractError';
  }
}

export const isRecord = (value: unknown): value is Record<string, unknown> =>
  typeof value === 'object' && value !== null && !Array.isArray(value);

export const requireRecord = (value: unknown, message?: string): Record<string, unknown> => {
  if (!isRecord(value)) throw new ApiContractError(message);
  return value;
};

export const requireArray = (value: unknown, message?: string): unknown[] => {
  if (!Array.isArray(value)) throw new ApiContractError(message);
  return value;
};

export const requireString = (value: unknown, message?: string): string => {
  if (typeof value !== 'string') throw new ApiContractError(message);
  return value;
};

export const requireNumber = (value: unknown, message?: string): number => {
  if (typeof value !== 'number' || !Number.isFinite(value)) throw new ApiContractError(message);
  return value;
};

export const requireBoolean = (value: unknown, message?: string): boolean => {
  if (typeof value !== 'boolean') throw new ApiContractError(message);
  return value;
};

export const optionalString = (value: unknown, message?: string): string | null | undefined => {
  if (value === null || value === undefined) return value;
  return requireString(value, message);
};

export const optionalNumber = (value: unknown, message?: string): number | null | undefined => {
  if (value === null || value === undefined) return value;
  return requireNumber(value, message);
};

export const optionalBoolean = (value: unknown, message?: string): boolean | null | undefined => {
  if (value === null || value === undefined) return value;
  return requireBoolean(value, message);
};
