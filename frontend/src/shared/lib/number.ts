export const clampAtLeast = (value: number, minimum: number): number =>
  Math.max(minimum, value);

export const clampWithin = (value: number, minimum: number, maximum: number): number =>
  Math.min(maximum, clampAtLeast(value, minimum));
