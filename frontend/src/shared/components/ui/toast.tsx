import React, { createContext, useCallback, useContext, useEffect, useMemo, useRef, useState } from 'react';
import { createPortal } from 'react-dom';
import { useLocation } from 'react-router-dom';

type ToastVariant = 'success' | 'error' | 'info';

interface ToastOptions {
  duration?: number;
  variant?: ToastVariant;
  persistent?: boolean;
}

interface ToastItem {
  id: number;
  message: string;
  variant: ToastVariant;
  expiresAt: number;
  persistent: boolean;
}

interface ToastContextValue {
  show: (message: string, options?: ToastOptions) => number;
}

const ToastContext = createContext<ToastContextValue | null>(null);

export const useToast = () => {
  const ctx = useContext(ToastContext);
  if (!ctx) throw new Error('useToast must be used within <ToastProvider>');
  return ctx;
};

export const ToastProvider: React.FC<{ children: React.ReactNode }> = ({ children }) => {
  const [toasts, setToasts] = useState<ToastItem[]>([]);
  const idRef = useRef(1);
  const timeoutsRef = useRef<Map<number, number>>(new Map());
  const location = useLocation();

  const clearToastTimeout = useCallback((id: number) => {
    if (typeof window === 'undefined') {
      return;
    }

    const timeoutId = timeoutsRef.current.get(id);
    if (timeoutId !== undefined) {
      window.clearTimeout(timeoutId);
      timeoutsRef.current.delete(id);
    }
  }, []);

  const clearAllToasts = useCallback(() => {
    if (typeof window !== 'undefined') {
      timeoutsRef.current.forEach((timeoutId) => window.clearTimeout(timeoutId));
      timeoutsRef.current.clear();
    }

    setToasts([]);
  }, []);

  const remove = useCallback(
    (id: number) => {
      clearToastTimeout(id);
      setToasts((list) => list.filter((t) => t.id !== id));
    },
    [clearToastTimeout],
  );

  const show = useCallback<ToastContextValue['show']>(
    (message, options = {}) => {
      const id = idRef.current++;
      const now = Date.now();
      const duration = options.duration ?? 30000;
      const variant = options.variant ?? 'success';
      const persistent = true;

      const item: ToastItem = {
        id,
        message,
        variant,
        persistent,
        expiresAt: now + duration,
      };

      setToasts((list) => [...list, item]);

      if (typeof window !== 'undefined') {
        const timeoutId = window.setTimeout(() => remove(id), duration);
        timeoutsRef.current.set(id, timeoutId);
      }

      return id;
    },
    [remove],
  );

  useEffect(() => {
    if (typeof window === 'undefined') {
      return undefined;
    }

    const interval = window.setInterval(() => {
      const now = Date.now();
      setToasts((list) => list.filter((t) => t.expiresAt > now));
    }, 1000);

    return () => window.clearInterval(interval);
  }, []);

  useEffect(
    () => () => {
      if (typeof window === 'undefined') return;
      timeoutsRef.current.forEach((timeoutId) => window.clearTimeout(timeoutId));
      timeoutsRef.current.clear();
    },
    [],
  );

  useEffect(() => {
    clearAllToasts();
  }, [location.pathname, clearAllToasts]);

  const value = useMemo(() => ({ show }), [show]);

  const target =
    typeof document !== 'undefined' ? document.getElementById('site-header-toasts') : null;

  return (
    <ToastContext.Provider value={value}>
      {children}
      {typeof document !== 'undefined' &&
        createPortal(
          <div
            className={
              target
                ? 'flex flex-col items-center gap-3 w-full'
                : 'fixed top-6 left-1/2 -translate-x-1/2 z-[100] flex flex-col items-center gap-3'
            }
            style={target ? undefined : {}}
          >
            {toasts.map((t) => (
              <div
                key={t.id}
                role="alert"
                aria-live="assertive"
                aria-atomic="true"
                className={[
                  'min-w-[320px] max-w-[92vw] rounded-xl border shadow-xl px-5 py-4 text-sm flex items-start gap-3 pointer-events-auto',
                  t.variant === 'success' ? 'bg-green-50 border-green-300 text-green-900' : '',
                  t.variant === 'error' ? 'bg-red-50 border-red-300 text-red-900' : '',
                  t.variant === 'info' ? 'bg-slate-50 border-slate-300 text-slate-900' : '',
                ]
                  .filter(Boolean)
                  .join(' ')}
              >
                <div
                  className={[
                    'mt-1 h-2.5 w-2.5 rounded-full',
                    t.variant === 'success' ? 'bg-green-600' : '',
                    t.variant === 'error' ? 'bg-red-600' : '',
                    t.variant === 'info' ? 'bg-slate-500' : '',
                  ]
                    .filter(Boolean)
                    .join(' ')}
                />
                <div className="flex-1">{t.message}</div>
                <button
                  type="button"
                  onClick={() => remove(t.id)}
                  className="ml-2 inline-flex items-center justify-center rounded-md px-2 py-1 text-current/70 hover:text-current"
                  aria-label="Fermer la notification"
                  title="Fermer"
                >
                  x
                </button>
              </div>
            ))}
          </div>,
          target ?? document.body,
        )}
    </ToastContext.Provider>
  );
};
