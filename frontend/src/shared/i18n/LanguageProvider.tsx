import {
  createContext,
  useCallback,
  useContext,
  useEffect as reactUseEffect,
  useEffect,
  useMemo,
  useRef,
  useState,
  type PropsWithChildren,
} from 'react';
import { useLocation } from 'react-router';

import frAuth from '@/shared/i18n/locales/fr/auth.json';
import frFooter from '@/shared/i18n/locales/fr/footer.json';
import frHeader from '@/shared/i18n/locales/fr/header.json';
import frHome from '@/shared/i18n/locales/fr/home.json';
import frNews from '@/shared/i18n/locales/fr/news.json';
import frNotFound from '@/shared/i18n/locales/fr/notFound.json';
import frSystem from '@/shared/i18n/locales/fr/system.json';
import frTraining from '@/shared/i18n/locales/fr/training.json';
import frCatalog from '@/shared/i18n/locales/fr/catalog.json';
import { createAutoDomTranslator } from '@/shared/i18n/autoDomTranslator';
import { translateTextForLocale } from '@/shared/i18n/dataTranslator';

type Language = 'fr' | 'en';

type LocaleBundle = {
  header: unknown;
  footer: unknown;
  notFound: unknown;
  home: unknown;
  auth: unknown;
  news: unknown;
  system: unknown;
  training: unknown;
  catalog: unknown;
};

export type { Language };

const mergedFr: LocaleBundle = {
  header: frHeader,
  footer: frFooter,
  notFound: frNotFound,
  home: frHome,
  auth: frAuth,
  news: frNews,
  system: frSystem,
  training: frTraining,
  catalog: frCatalog,
};

type Variables = Record<string, string | number>;

type TranslationValue = string | TranslationValue[] | { [key: string]: TranslationValue };

type FlatLocaleEntry = { key: string; value: string };

const flattenTranslations = (node: unknown, prefix = ''): Array<FlatLocaleEntry> => {
  if (typeof node === 'string') {
    return [{ key: prefix, value: node }];
  }
  if (node === null || typeof node !== 'object') return [];
  if (Array.isArray(node)) {
    return node.flatMap((value, index) =>
      flattenTranslations(value, prefix.length ? `${prefix}.${index}` : String(index)),
    );
  }
  return Object.entries(node as TranslationValue).flatMap(([key, value]) => {
    const path = prefix.length > 0 ? `${prefix}.${key}` : key;
    if (typeof value === 'string') {
      return [{ key: path, value }];
    }
    if (typeof value === 'object' && value !== null) {
      return flattenTranslations(value, path);
    }
    return [];
  });
};

const sourceTranslationCatalog = flattenTranslations(mergedFr).reduce<Record<string, string>>((acc, entry) => {
  acc[entry.key] = entry.value;
  return acc;
}, {});

const resolvePath = (node: LocaleBundle, key: string): string | undefined => {
  const value = key.split('.').reduce<unknown>((acc, segment) => {
    if (acc === null || acc === undefined) return undefined;
    if (Array.isArray(acc)) {
      const index = Number(segment);
      return Number.isInteger(index) ? acc[index] : undefined;
    }
    if (typeof acc === 'object' && segment in (acc as Record<string, unknown>)) {
      return (acc as Record<string, unknown>)[segment];
    }
    return undefined;
  }, node);

  return typeof value === 'string' ? value : undefined;
};

const interpolate = (template: string, variables?: Variables): string => {
  if (!variables) return template;
  return template.replace(/\{\{\s*([\w.]+)\s*\}\}/g, (_, key) => {
    return Object.hasOwn(variables, key) ? String(variables[key]) : `{{${key}}}`;
  });
};

export const detectDefaultLanguage = (): Language => {
  if (typeof localStorage !== 'undefined') {
    const stored = localStorage.getItem(storageKey);
    if (stored === 'en' || stored === 'fr') {
      return stored;
    }
  }

  if (typeof navigator === 'undefined') return 'fr';
  const browser = navigator.language.toLowerCase();
  return browser.startsWith('en') ? 'en' : 'fr';
};

export const availableLanguages: Array<{ code: Language; flag: string; labelKey: string; ariaLabelKey: string }> = [
  {
    code: 'fr',
    flag: '🇫🇷',
    labelKey: 'header.language.french',
    ariaLabelKey: 'header.language.frenchAria',
  },
  {
    code: 'en',
    flag: '🇬🇧',
    labelKey: 'header.language.english',
    ariaLabelKey: 'header.language.englishAria',
  },
];

const storageKey = 'hocatec.language';

type LanguageContextValue = {
  language: Language;
  t: (key: string, variables?: Variables) => string;
  setLanguage: (language: Language) => void;
  options: typeof availableLanguages;
};

const LanguageContext = createContext<LanguageContextValue | null>(null);

export const LanguageProvider = ({ children }: PropsWithChildren) => {
  const [language, setLanguage] = useState<Language>(() => detectDefaultLanguage());
  const autoDomTranslatorRef = useRef<ReturnType<typeof createAutoDomTranslator> | null>(null);
  const translationCacheRef = useRef<Record<Language, Record<string, string>>>({
    fr: sourceTranslationCatalog,
    en: {},
  });
  const pendingTranslationsRef = useRef(new Set<string>());
  const isMountedRef = useRef(true);
  const [, forceRerender] = useState(0);

  const location = useLocation();

  reactUseEffect(() => {
    const normalized = location.pathname ?? '';
    const match = normalized.match(/^\/(fr|en)(?:\/|$)/);

    if (match && match[1] === 'en') {
      setLanguage('en');
      return;
    }

    if (match && match[1] === 'fr') {
      setLanguage('fr');
    }
  }, [location.pathname]);

  useEffect(() => {
    isMountedRef.current = true;
    return () => {
      isMountedRef.current = false;
    };
  }, []);

  useEffect(() => {
    if (typeof localStorage !== 'undefined') {
      localStorage.setItem(storageKey, language);
    }
    if (typeof document !== 'undefined') {
      document.documentElement.lang = language;
    }
  }, [language]);

  useEffect(() => {
    const translator = createAutoDomTranslator();
    autoDomTranslatorRef.current = translator;
    void translator.setLanguage(language);

    return () => {
      translator.stop();
      if (autoDomTranslatorRef.current === translator) {
        autoDomTranslatorRef.current = null;
      }
    };
  }, []);

  const requestEnglishTranslation = useCallback(
    (key: string, sourceText: string) => {
      if (language !== 'en') return;
      if (pendingTranslationsRef.current.has(key)) return;
      pendingTranslationsRef.current.add(key);

      void translateTextForLocale(sourceText, language, 'fr')
        .then((translated) => {
          if (!isMountedRef.current) return;
          translationCacheRef.current.en[key] = translated;
          forceRerender((value) => value + 1);
        })
        .catch(() => {
          if (!isMountedRef.current) return;
          delete translationCacheRef.current.en[key];
        })
        .finally(() => {
          pendingTranslationsRef.current.delete(key);
        });
    },
    [language],
  );

  useEffect(() => {
    void autoDomTranslatorRef.current?.setLanguage(language);
  }, [language]);

  const t = useCallback(
    (key: string, variables?: Variables) => {
      const sourceText = resolvePath(mergedFr, key);
      if (typeof sourceText !== 'string') return key;

      const normalizedSource = sourceText.trim();
      if (language === 'fr' || !normalizedSource) return interpolate(normalizedSource, variables);

      const translatedValue = translationCacheRef.current.en[key];
      if (translatedValue) return interpolate(translatedValue, variables);

      requestEnglishTranslation(key, normalizedSource);
      return interpolate(normalizedSource, variables);
    },
    [language, requestEnglishTranslation],
  );

  const context = useMemo(
    () => ({
      language,
      setLanguage,
      t,
      options: availableLanguages,
    }),
    [language, t],
  );

  return <LanguageContext.Provider value={context}>{children}</LanguageContext.Provider>;
};

export const useTranslation = () => {
  const context = useContext(LanguageContext);
  if (!context) {
    throw new Error('useTranslation must be used inside LanguageProvider');
  }
  return context;
};

export const getLanguageCatalog = () =>
  flattenTranslations(mergedFr).sort((left, right) => left.key.localeCompare(right.key));
