import { parseNonNegativeInteger } from './parsers';

const LOYALTY_POINT_BLOCK_SIZE = 100;

export const normalizeLoyaltyPoints = (points: number, blockSize = LOYALTY_POINT_BLOCK_SIZE) =>
  Math.floor(Math.max(points, 0) / blockSize) * blockSize;

export const parseAndNormalizeLoyaltyPoints = (value: string, fallback = 0, blockSize = LOYALTY_POINT_BLOCK_SIZE) =>
  normalizeLoyaltyPoints(parseNonNegativeInteger(value, fallback), blockSize);

export const getDefaultConvertPoints = (points: number, blockSize = LOYALTY_POINT_BLOCK_SIZE) => {
  if (points <= 0) return '0';
  const normalized = normalizeLoyaltyPoints(points, blockSize);
  return String(normalized > 0 ? normalized : points);
};

export const convertLoyaltyPointsToEuroCents = (
  points: number,
  pointsPerEuroConverted = LOYALTY_POINT_BLOCK_SIZE,
) => Math.floor(points / pointsPerEuroConverted) * 100;
