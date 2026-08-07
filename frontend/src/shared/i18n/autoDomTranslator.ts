import { translateTextForLocale, type Language } from '@/shared/i18n/dataTranslator';

type ValueState = Partial<Record<Language, string>>;

type TextNodeState = {
  values: ValueState;
  renderedLanguage: Language;
  leading: string;
  trailing: string;
};

type AttributeValueState = {
  values: ValueState;
  renderedLanguage: Language;
};

type AutoDomTranslator = {
  setLanguage: (language: Language) => Promise<void>;
  stop: () => void;
};

const isBrowser = typeof window !== 'undefined' && typeof document !== 'undefined';

const textOnlyRegex = /[A-Za-zÀ-ÖØ-öø-ÿ]/u;
const ignoreTags = new Set(['SCRIPT', 'STYLE', 'NOSCRIPT', 'CODE']);
const translatableAttributes = ['title', 'placeholder', 'aria-label', 'alt', 'value', 'label'];

const splitWhitespace = (value: string) => {
  const leading = value.match(/^\s*/u)?.[0] ?? '';
  const trailing = value.match(/\s*$/u)?.[0] ?? '';
  return {
    leading,
    trailing,
    content: value.slice(leading.length, value.length - trailing.length),
  };
};

const isTranslatableNode = (node: Text) => {
  const parent = node.parentElement;
  if (!parent) return false;
  if (parent.closest('[data-i18n-ignore]')) return false;
  const parentTag = parent.tagName.toUpperCase();
  if (ignoreTags.has(parentTag)) return false;

  const text = node.textContent ?? '';
  if (!text.trim()) return false;
  if (!textOnlyRegex.test(text)) return false;

  return true;
};

const isTranslatableAttribute = (element: Element, attributeName: string) => {
  if (element.hasAttribute('data-i18n-ignore')) return false;
  const value = element.getAttribute(attributeName) ?? '';
  if (!value.trim()) return false;
  if (!textOnlyRegex.test(value)) return false;
  return true;
};

const collectTextNodes = (root: ParentNode): Text[] => {
  const walker = document.createTreeWalker(root, NodeFilter.SHOW_TEXT, {
    acceptNode: (node) => (isTranslatableNode(node as Text) ? NodeFilter.FILTER_ACCEPT : NodeFilter.FILTER_REJECT),
  });

  const nodes: Text[] = [];
  while (walker.nextNode()) {
    nodes.push(walker.currentNode as Text);
  }

  return nodes;
};

const collectAttributeNodes = (root: ParentNode): Array<{ element: Element; attributeName: string }> => {
  const query = translatableAttributes.map((attribute) => `[${attribute}]`).join(',');
  const elements = Array.from(root.querySelectorAll<HTMLElement>(query));
  const payload: Array<{ element: Element; attributeName: string }> = [];
  for (const element of elements) {
    if (element.closest('[data-i18n-ignore]')) continue;
    for (const attributeName of translatableAttributes) {
      if (element.hasAttribute(attributeName)) {
        payload.push({ element, attributeName });
      }
    }
  }
  return payload;
};

const getTextState = (
  node: Text,
  targetLanguage: Language,
  map: WeakMap<Text, TextNodeState>,
): TextNodeState => {
  const currentText = node.textContent ?? '';
  let state = map.get(node);
  const { leading, trailing, content } = splitWhitespace(currentText);

  if (!state) {
    state = {
      values: { [targetLanguage]: content },
      renderedLanguage: targetLanguage,
      leading,
      trailing,
    };
    map.set(node, state);
    return state;
  }

  const currentRendered = state.values[state.renderedLanguage] ?? '';
  if (currentRendered !== content) {
    state.values[state.renderedLanguage] = content;
    const alternateLanguage: Language = state.renderedLanguage === 'fr' ? 'en' : 'fr';
    delete state.values[alternateLanguage];
    state.leading = leading;
    state.trailing = trailing;
  } else {
    state.leading = leading;
    state.trailing = trailing;
  }

  return state;
};

const getAttributeState = (
  element: Element,
  attributeName: string,
  targetLanguage: Language,
  map: WeakMap<Element, Record<string, AttributeValueState>>,
) => {
  const raw = element.getAttribute(attributeName) ?? '';
  const value = raw.trim();

  let states = map.get(element);
  if (!states) {
    states = {};
    map.set(element, states);
  }

  let state = states[attributeName];
  if (!state) {
    state = {
      values: { [targetLanguage]: value },
      renderedLanguage: targetLanguage,
    };
    states[attributeName] = state;
    return state;
  }

  const renderedValue = state.values[state.renderedLanguage] ?? '';
  if (renderedValue !== value) {
    state.values[state.renderedLanguage] = value;
    const alternateLanguage: Language = state.renderedLanguage === 'fr' ? 'en' : 'fr';
    delete state.values[alternateLanguage];
  }

  return state;
};

const getAlternativeLanguage = (state: { values: ValueState }, language: Language): Language | undefined => {
  const other = language === 'fr' ? 'en' : 'fr';
  if (state.values[other] !== undefined) return other;
  if (state.values[language] !== undefined) return language;
  return undefined;
};

const applyTranslatedText = (node: Text, state: TextNodeState, translatedText: string) => {
  node.textContent = `${state.leading}${translatedText}${state.trailing}`;
};

const hasValue = (value?: string): value is string => value !== undefined && value.trim() !== '';

const createTranslatorRunner = () => {
  const nodeStates = new WeakMap<Text, TextNodeState>();
  const attributeStates = new WeakMap<Element, Record<string, AttributeValueState>>();

  let currentLanguage: Language = 'fr';
  let running = false;
  let stopRequested = false;
  let pending = false;

  const translateTextNode = async (node: Text) => {
    const state = getTextState(node, currentLanguage, nodeStates);
    if (state.renderedLanguage === currentLanguage && state.values[currentLanguage] !== undefined) return;

    const sourceLanguage = getAlternativeLanguage(state, currentLanguage);
    if (!sourceLanguage) return;

    const sourceText = state.values[sourceLanguage];
    if (!hasValue(sourceText)) return;

    const translated = await translateTextForLocale(sourceText, currentLanguage, sourceLanguage);
    state.values[currentLanguage] = translated;
    applyTranslatedText(node, state, translated);
    state.renderedLanguage = currentLanguage;
  };

  const translateAttribute = async (element: Element, attributeName: string) => {
    const state = getAttributeState(element, attributeName, currentLanguage, attributeStates);
    if (state.renderedLanguage === currentLanguage && state.values[currentLanguage] !== undefined) {
      return;
    }

    const sourceLanguage = getAlternativeLanguage(state, currentLanguage);
    if (!sourceLanguage) return;

    const sourceText = state.values[sourceLanguage];
    if (!hasValue(sourceText)) return;

    const translated = await translateTextForLocale(sourceText, currentLanguage, sourceLanguage);
    state.values[currentLanguage] = translated;
    element.setAttribute(attributeName, translated);
    state.renderedLanguage = currentLanguage;
  };

  const process = async () => {
    if (running || stopRequested || !isBrowser) {
      return;
    }

    if (!pending) return;
    running = true;
    pending = false;

    try {
      const body = document.body;
      if (!body) return;

      const nodes = collectTextNodes(body);
      for (const node of nodes) {
        await translateTextNode(node);
      }

      const attributeNodes = collectAttributeNodes(body);
      for (const { element, attributeName } of attributeNodes) {
        if (!isTranslatableAttribute(element, attributeName)) continue;
        await translateAttribute(element, attributeName);
      }
    } finally {
      running = false;

      if (stopRequested) return;
      if (pending) {
        void process();
      }
    }
  };

  const schedule = () => {
    if (stopRequested) return;
    pending = true;
    if (running) return;
    void process();
  };

  const observer = isBrowser
    ? new MutationObserver(() => {
        if (isBrowser) {
          schedule();
        }
      })
    : null;

  const start = () => {
    if (!isBrowser || !observer || stopRequested) return;
    observer.observe(document.body, { childList: true, subtree: true, characterData: true });
  };

  return {
    setLanguage: async (language: Language) => {
      currentLanguage = language;
      start();
      schedule();

      while (pending || running) {
        await new Promise((resolve) => setTimeout(resolve, 25));
      }
    },
    stop: () => {
      stopRequested = true;
      observer?.disconnect();
      pending = false;
    },
    refresh: () => {
      schedule();
    },
    start,
  };
};

export const createAutoDomTranslator = (): AutoDomTranslator => {
  if (!isBrowser) {
    return {
      setLanguage: async () => {},
      stop: () => {},
    };
  }

  const runner = createTranslatorRunner();
  runner.start();

  return {
    setLanguage: async (language: Language) => {
      await runner.setLanguage(language);
    },
    stop: () => {
      runner.stop();
    },
  };
};
