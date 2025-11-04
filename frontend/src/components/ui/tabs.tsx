import type { HTMLAttributes, PropsWithChildren } from 'react';
import { createContext, useCallback, useContext, useEffect, useId, useMemo, useState } from 'react';

import { cn } from '@/shared/lib/cn';

interface TabsContextValue {
  activeValue: string;
  setActiveValue: (value: string) => void;
  idPrefix: string;
}

const TabsContext = createContext<TabsContextValue | null>(null);

const useTabsContext = (component: string) => {
  const context = useContext(TabsContext);
  if (!context) {
    throw new Error(`${component} must be used within <Tabs>`);
  }
  return context;
};

export interface TabsProps extends PropsWithChildren {
  defaultValue: string;
  value?: string;
  onValueChange?: (value: string) => void;
  className?: string;
}

export const Tabs = ({
  defaultValue,
  value,
  onValueChange,
  className,
  children,
}: TabsProps) => {
  const [internalValue, setInternalValue] = useState(defaultValue);
  const idPrefix = useId();

  useEffect(() => {
    if (value === undefined) {
      setInternalValue(defaultValue);
    }
  }, [defaultValue, value]);

  const activeValue = value ?? internalValue;

  const setActiveValue = useCallback(
    (nextValue: string) => {
      onValueChange?.(nextValue);
      if (value === undefined) {
        setInternalValue(nextValue);
      }
    },
    [onValueChange, value],
  );

  const contextValue = useMemo(
    () => ({
      activeValue,
      setActiveValue,
      idPrefix,
    }),
    [activeValue, idPrefix, setActiveValue],
  );

  return (
    <TabsContext.Provider value={contextValue}>
      <div className={cn('flex flex-col gap-6', className)}>{children}</div>
    </TabsContext.Provider>
  );
};

export const TabsList = ({ className, ...props }: HTMLAttributes<HTMLDivElement>) => (
  <div
    role="tablist"
    className={cn(
      'gap-2 rounded-2xl bg-slate-900/70 p-1 text-slate-200 shadow-inner ring-1 ring-inset ring-white/10',
      className,
    )}
    {...props}
  />
);

interface TabsTriggerProps extends HTMLAttributes<HTMLButtonElement> {
  value: string;
}

export const TabsTrigger = ({ value, className, children, ...props }: TabsTriggerProps) => {
  const { activeValue, setActiveValue, idPrefix } = useTabsContext('TabsTrigger');
  const isActive = activeValue === value;

  return (
    <button
      type="button"
      role="tab"
      aria-selected={isActive}
      id={`${idPrefix}-trigger-${value}`}
      aria-controls={`${idPrefix}-content-${value}`}
      onClick={() => setActiveValue(value)}
      className={cn(
        'w-full rounded-xl px-4 py-2 text-sm font-medium transition-all duration-200',
        'focus-visible:outline focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-brand-400',
        isActive
          ? 'bg-brand-500 text-slate-950 shadow-lg shadow-brand-500/30'
          : 'text-slate-300 hover:text-white hover:bg-slate-800/70',
        className,
      )}
      {...props}
    >
      {children}
    </button>
  );
};

interface TabsContentProps extends HTMLAttributes<HTMLDivElement> {
  value: string;
}

export const TabsContent = ({ value, className, ...props }: TabsContentProps) => {
  const { activeValue, idPrefix } = useTabsContext('TabsContent');
  if (activeValue !== value) {
    return null;
  }

  return (
    <div
      role="tabpanel"
      id={`${idPrefix}-content-${value}`}
      aria-labelledby={`${idPrefix}-trigger-${value}`}
      className={cn(className)}
      {...props}
    />
  );
};
