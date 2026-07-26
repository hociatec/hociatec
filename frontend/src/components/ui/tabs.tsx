import type { ButtonHTMLAttributes, HTMLAttributes, PropsWithChildren } from 'react';
import { cn } from '@/shared/lib/cn';
import { TabsContext, useTabsContext, useTabsController, useTabsTrigger, toSafeTabIdPart } from './tabsLogic';

export interface TabsProps extends PropsWithChildren {
  defaultValue: string;
  value?: string;
  onValueChange?: (value: string) => void;
  className?: string;
}

export const Tabs = ({ defaultValue, value, onValueChange, className, children }: TabsProps) => {
  const controller = useTabsController(defaultValue, value, onValueChange);
  return <TabsContext.Provider value={controller}><div className={cn('flex flex-col gap-6', className)}>{children}</div></TabsContext.Provider>;
};

export const TabsList = ({ className, ...props }: HTMLAttributes<HTMLDivElement>) => (
  <div {...props} role="tablist" className={cn('flex gap-2 rounded-2xl bg-slate-900/70 p-1 text-slate-200 shadow-inner ring-1 ring-inset ring-white/10', className)} />
);

interface TabsTriggerProps extends Omit<ButtonHTMLAttributes<HTMLButtonElement>, 'value'> {
  value: string;
}

export const TabsTrigger = ({ value, className, children, onClick, onKeyDown, disabled, ...props }: TabsTriggerProps) => {
  const { setActiveValue } = useTabsContext('TabsTrigger');
  const { elementRef, handleKeyDown, isActive, triggerId, contentId } = useTabsTrigger({ value, disabled, onKeyDown });
  return (
    <button
      {...props}
      ref={elementRef}
      type="button"
      role="tab"
      aria-selected={isActive}
      id={triggerId}
      aria-controls={contentId}
      tabIndex={isActive ? 0 : -1}
      disabled={disabled}
      onClick={(event) => {
        onClick?.(event);
        if (!event.defaultPrevented) {
          setActiveValue(value);
        }
      }}
      onKeyDown={handleKeyDown}
      className={cn(
        'w-full rounded-xl px-4 py-2 text-sm font-medium transition-all duration-200',
        'focus-visible:outline focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-brand-400',
        isActive ? 'bg-brand-500 text-slate-950 shadow-lg shadow-brand-500/30' : 'text-slate-300 hover:bg-slate-800/70 hover:text-white',
        className,
      )}
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
  const safeValue = toSafeTabIdPart(value);
  return <div {...props} role="tabpanel" id={`${idPrefix}-content-${safeValue}`} aria-labelledby={`${idPrefix}-trigger-${safeValue}`} hidden={activeValue !== value} className={cn(className)} />;
};
