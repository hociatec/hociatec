import type { ButtonHTMLAttributes, KeyboardEvent } from 'react';
import { createContext, useCallback, useContext, useEffect, useId, useMemo, useRef, useState } from 'react';

export interface TabsContextValue {
  activeValue: string;
  setActiveValue: (value: string) => void;
  idPrefix: string;
  registerTab: (value: string, element: HTMLButtonElement | null, disabled?: boolean) => void;
  getEnabledTabValues: () => string[];
  focusTab: (value: string) => void;
}

export const TabsContext = createContext<TabsContextValue | null>(null);

export const useTabsContext = (component: string) => {
  const context = useContext(TabsContext);
  if (!context) {
    throw new Error(`${component} must be used within <Tabs>`);
  }
  return context;
};

export const toSafeTabIdPart = (value: string) => {
  const normalized = value.trim().toLowerCase().replace(/[^a-z0-9_-]+/gi, '-').replace(/^-|-$/g, '') || 'tab';
  let hash = 0;
  for (let index = 0; index < value.length; index += 1) {
    hash = (hash * 31 + value.charCodeAt(index)) | 0;
  }
  return `${normalized}-${(hash >>> 0).toString(36)}`;
};

export const useTabsController = (
  defaultValue: string,
  value: string | undefined,
  onValueChange: ((value: string) => void) | undefined,
) => {
  const [internalValue, setInternalValue] = useState(() => defaultValue);
  const tabElements = useRef(new Map<string, { element: HTMLButtonElement; disabled: boolean }>());
  const idPrefix = useId();
  const activeValue = value ?? internalValue;

  const setActiveValue = useCallback((nextValue: string) => {
    onValueChange?.(nextValue);
    if (value === undefined) {
      setInternalValue(nextValue);
    }
  }, [onValueChange, value]);

  const registerTab = useCallback((tabValue: string, element: HTMLButtonElement | null, disabled = false) => {
    if (element) {
      tabElements.current.set(tabValue, { element, disabled });
      return;
    }
    tabElements.current.delete(tabValue);
  }, []);

  const getEnabledTabValues = useCallback(() => Array.from(tabElements.current.entries())
    .filter(([, tab]) => !tab.disabled)
    .sort(([, left], [, right]) => {
      if (left.element === right.element) return 0;
      return left.element.compareDocumentPosition(right.element) & Node.DOCUMENT_POSITION_FOLLOWING ? -1 : 1;
    })
    .map(([tabValue]) => tabValue), []);

  const focusTab = useCallback((tabValue: string) => {
    tabElements.current.get(tabValue)?.element.focus();
  }, []);

  return useMemo(() => ({ activeValue, setActiveValue, idPrefix, registerTab, getEnabledTabValues, focusTab }), [
    activeValue,
    focusTab,
    getEnabledTabValues,
    idPrefix,
    registerTab,
    setActiveValue,
  ]);
};

interface UseTabsTriggerOptions extends Pick<ButtonHTMLAttributes<HTMLButtonElement>, 'disabled' | 'onKeyDown'> {
  value: string;
}

export const useTabsTrigger = ({ value, disabled, onKeyDown }: UseTabsTriggerOptions) => {
  const { activeValue, idPrefix, registerTab, getEnabledTabValues, setActiveValue, focusTab } = useTabsContext('TabsTrigger');
  const elementRef = useRef<HTMLButtonElement | null>(null);
  const safeValue = toSafeTabIdPart(value);

  useEffect(() => {
    registerTab(value, elementRef.current, disabled);
    return () => registerTab(value, null);
  }, [disabled, registerTab, value]);

  const handleKeyDown = (event: KeyboardEvent<HTMLButtonElement>) => {
    onKeyDown?.(event);
    if (event.defaultPrevented || disabled) return;

    const tabValues = getEnabledTabValues();
    const currentIndex = tabValues.indexOf(value);
    if (currentIndex < 0) return;

    let nextIndex: number | null = null;
    if (event.key === 'ArrowRight') nextIndex = (currentIndex + 1) % tabValues.length;
    if (event.key === 'ArrowLeft') nextIndex = (currentIndex - 1 + tabValues.length) % tabValues.length;
    if (event.key === 'Home') nextIndex = 0;
    if (event.key === 'End') nextIndex = tabValues.length - 1;
    if (nextIndex === null) return;

    event.preventDefault();
    const nextValue = tabValues[nextIndex];
    setActiveValue(nextValue);
    focusTab(nextValue);
  };

  return {
    activeValue,
    elementRef,
    handleKeyDown,
    isActive: activeValue === value,
    triggerId: `${idPrefix}-trigger-${safeValue}`,
    contentId: `${idPrefix}-content-${safeValue}`,
  };
};
