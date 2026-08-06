let fallbackCounter = 0;

const randomBytes = (length: number) => {
  const bytes = new Uint8Array(length);
  if (typeof globalThis.crypto?.getRandomValues === 'function') {
    globalThis.crypto.getRandomValues(bytes);
    return bytes;
  }

  fallbackCounter += 1;
  const fallback = `${Date.now().toString(36)}${fallbackCounter.toString(36)}`;
  for (let index = 0; index < bytes.length; index += 1) {
    bytes[index] = fallback.charCodeAt(index % fallback.length) % 256;
  }

  return bytes;
};

export const createRandomHex = (byteLength = 8) =>
  Array.from(randomBytes(byteLength), (byte) => byte.toString(16).padStart(2, '0')).join('');

export const createRandomId = (prefix: string) => {
  if (typeof globalThis.crypto?.randomUUID === 'function') {
    return `${prefix}_${globalThis.crypto.randomUUID()}`;
  }

  return `${prefix}_${Date.now().toString(36)}_${createRandomHex(6)}`;
};

export const createRandomCodeSuffix = (byteLength = 3) =>
  createRandomHex(byteLength).toUpperCase();
