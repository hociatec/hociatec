export type Language = 'fr' | 'en';

type CacheStore = Record<string, string>;

const normalizeTranslatedText = (value: string) =>
  value
    .replace(/\bconnection\b/g, 'se connecté')
    .replace(/\bConnection\b/g, 'Se connecté');

const runtimeEnvironment = (import.meta.env.VITE_APP_ENV ?? (import.meta.env.PROD ? 'production' : 'development')).trim();
const isDevelopmentLike = runtimeEnvironment === 'development';
const translationCachePrefix = 'hociatec.dynamicTranslations.';
const translationCacheKey = `${translationCachePrefix}v5`;
const legacyTranslationCachePrefix = translationCachePrefix;
const legacyTranslationVersions = ['v1', 'v2', 'v3', 'v4'];

const purgeLegacyTranslationCaches = () => {
  if (typeof window === 'undefined') return;

  const removeLegacyByVersion = () => {
    legacyTranslationVersions.forEach((version) => {
      window.localStorage.removeItem(`${legacyTranslationCachePrefix}${version}`);
    });
  };

  const removeMismatchedVersions = () => {
    const { length } = window.localStorage;
    const keysToRemove: string[] = [];
    for (let index = 0; index < length; index += 1) {
      const key = window.localStorage.key(index);
      if (!key) continue;
      if (!key.startsWith(legacyTranslationCachePrefix)) continue;
      if (key === translationCacheKey) continue;
      keysToRemove.push(key);
    }
    keysToRemove.forEach((key) => {
      window.localStorage.removeItem(key);
    });
  };

  removeLegacyByVersion();
  removeMismatchedVersions();
};

purgeLegacyTranslationCaches();
const defaultEndpoint = isDevelopmentLike
  ? 'http://127.0.0.1:5000/translate'
  : '/api/translate';
const fallbackEndpoint = defaultEndpoint;
const normalizeEndpoint = (value: string) => value.trim();
const endpoints = (() => {
  const primary = import.meta.env.VITE_LIBRETRANSLATE_ENDPOINT ?? defaultEndpoint;
  const fallback = import.meta.env.VITE_LIBRETRANSLATE_FALLBACK_ENDPOINT ?? fallbackEndpoint;
  return [
    primary,
    ...fallback
      .split(',')
      .map((value: string) => normalizeEndpoint(value))
      .filter((value: string) => value.length > 0),
  ]
    .map((value: string) => normalizeEndpoint(value))
    .filter((value) => value.length > 0)
    .filter((value, index, list) => list.indexOf(value) === index);
})();
const apiKey = import.meta.env.VITE_LIBRETRANSLATE_API_KEY ?? '';

const hasWindow = () => typeof window !== 'undefined';
const normalizeText = (value: string) => value.trim();

const getStorageCache = (): CacheStore => {
  if (!hasWindow()) return {};
  const stored = window.localStorage.getItem(translationCacheKey);
  if (!stored) return {};
  try {
    const parsed = JSON.parse(stored);
    return parsed && typeof parsed === 'object' ? (parsed as CacheStore) : {};
  } catch {
    return {};
  }
};

const hashText = (text: string) => {
  let hash = 0;
  for (let index = 0; index < text.length; index += 1) {
    hash = (hash * 31 + text.charCodeAt(index)) % 2147483647;
  }
  return hash.toString(36);
};

const cache = new Map<string, string>(Object.entries(getStorageCache()));

const persistCache = () => {
  if (!hasWindow()) return;
  const payload: CacheStore = {};
  for (const [key, value] of cache.entries()) {
    payload[key] = value;
  }
  window.localStorage.setItem(translationCacheKey, JSON.stringify(payload));
};

const buildCacheKey = (sourceLanguage: Language, targetLanguage: Language, text: string) =>
  `${sourceLanguage}:${targetLanguage}:${hashText(text)}`;

const requestLibreTranslate = async (url: string, text: string, sourceLanguage: Language, targetLanguage: Language) => {
  if (!hasWindow() && typeof fetch === 'undefined') {
    throw new Error('fetch unavailable');
  }

  const body: Record<string, string> = {
    q: text,
    source: sourceLanguage,
    target: targetLanguage,
    format: 'text',
  };

  if (apiKey) {
    body.api_key = apiKey;
  }

  const response = await fetch(url, {
    method: 'POST',
    headers: { 'Content-Type': 'application/json' },
    body: JSON.stringify(body),
  });

  if (!response.ok) {
    throw new Error(`LibreTranslate error ${response.status}`);
  }

  const payload = await response.json();
  return payload.translatedText ?? payload.translated_text ?? text;
};

const requestTranslation = async (text: string, sourceLanguage: Language, targetLanguage: Language) => {
  let lastError: Error = new Error('LibreTranslate unavailable');
  for (const endpoint of endpoints) {
    try {
      return await requestLibreTranslate(endpoint, text, sourceLanguage, targetLanguage);
    } catch (error) {
      if (error instanceof Error) {
        lastError = error;
      }
    }
  }
  throw lastError;
};

export const translateTextForLocale = async (
  text: string,
  targetLanguage: Language,
  sourceLanguage: Language = 'fr',
): Promise<string> => {
  const normalized = normalizeText(text);
  if (!normalized || targetLanguage === sourceLanguage) return text;
  const key = buildCacheKey(sourceLanguage, targetLanguage, normalized);
  const existing = cache.get(key);
  if (existing) return normalizeTranslatedText(existing);

  try {
    const translated = await requestTranslation(normalized, sourceLanguage, targetLanguage);
    const normalizedTranslated = normalizeTranslatedText(translated);
    cache.set(key, normalizedTranslated);
    persistCache();
    return normalizedTranslated;
  } catch {
    cache.delete(key);
    persistCache();
    return text;
  }
};

export const translateObjectTextFields = async <T>(
  source: T,
  fields: string[],
  targetLanguage: Language,
  sourceLanguage: Language = 'fr',
): Promise<T> => {
  if (targetLanguage === sourceLanguage) return source;

  const cloned = typeof structuredClone === 'function' ? structuredClone(source) : JSON.parse(JSON.stringify(source));

  const getValue = (object: any, path: string[]) =>
    path.reduce((acc: any, segment) => {
      if (!acc || typeof acc !== 'object') return undefined;
      return acc[segment];
    }, object);

  const setValue = (object: any, path: string[], value: string) => {
    let cursor = object;
    path.forEach((segment, index) => {
      if (index === path.length - 1) {
        cursor[segment] = value;
        return;
      }
      cursor = cursor[segment];
    });
  };

  for (const path of fields) {
    const segments = path.split('.');
    const currentValue = getValue(cloned, segments);
    if (typeof currentValue !== 'string' || !normalizeText(currentValue)) continue;
    const translated = await translateTextForLocale(currentValue, targetLanguage, sourceLanguage);
    setValue(cloned, segments, translated);
  }

  return cloned as T;
};
