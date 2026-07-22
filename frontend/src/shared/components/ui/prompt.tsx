import {
  createContext,
  useCallback,
  useContext,
  useMemo,
  useState,
  type HTMLAttributes,
  type HTMLInputTypeAttribute,
  type ReactNode,
} from 'react';

import { Dialog, DialogBackdrop, DialogDescription, DialogPanel, DialogTitle } from '@/shared/components/ui/dialog';

type PromptOptions = {
  title: string;
  description?: string;
  label: string;
  defaultValue?: string;
  inputMode?: HTMLAttributes<HTMLInputElement>['inputMode'];
  inputType?: HTMLInputTypeAttribute;
  confirmLabel?: string;
  cancelLabel?: string;
};

type PendingPrompt = PromptOptions & {
  resolve: (value: string | null) => void;
};

const PromptContext = createContext<((options: PromptOptions) => Promise<string | null>) | null>(null);

export const usePrompt = () => {
  const prompt = useContext(PromptContext);

  if (!prompt) {
    throw new Error('usePrompt must be used within <PromptProvider>');
  }

  return prompt;
};

export const PromptProvider = ({ children }: { children: ReactNode }) => {
  const [pendingPrompt, setPendingPrompt] = useState<PendingPrompt | null>(null);
  const [value, setValue] = useState('');

  const prompt = useCallback((options: PromptOptions) => (
    new Promise<string | null>((resolve) => {
      setValue(options.defaultValue ?? '');
      setPendingPrompt({ ...options, resolve });
    })
  ), []);

  const close = useCallback((nextValue: string | null) => {
    const current = pendingPrompt;
    setPendingPrompt(null);
    current?.resolve(nextValue);
  }, [pendingPrompt]);

  const contextValue = useMemo(() => prompt, [prompt]);

  return (
    <PromptContext.Provider value={contextValue}>
      {children}
      <Dialog open={Boolean(pendingPrompt)} onClose={() => close(null)} className="relative z-50">
        <DialogBackdrop className="fixed inset-0 bg-brand-900/70" />
        <div className="fixed inset-0 flex items-center justify-center px-4 py-6">
          <DialogPanel className="w-full max-w-md rounded-2xl border border-brand-100 bg-white p-6 shadow-2xl">
            <DialogTitle className="text-xl font-bold text-brand-900">
              {pendingPrompt?.title}
            </DialogTitle>
            {pendingPrompt?.description ? (
              <DialogDescription className="mt-2 text-sm text-stone-600">
                {pendingPrompt.description}
              </DialogDescription>
            ) : null}
            <form
              className="mt-5 space-y-4"
              onSubmit={(event) => {
                event.preventDefault();
                close(value);
              }}
            >
              <label className="register-form__field">
                <span>{pendingPrompt?.label}</span>
                <input
                  autoFocus
                  className="register-form__input"
                  type={pendingPrompt?.inputType ?? 'text'}
                  inputMode={pendingPrompt?.inputMode}
                  value={value}
                  onChange={(event) => setValue(event.target.value)}
                />
              </label>
              <div className="flex flex-col-reverse gap-3 sm:flex-row sm:justify-end">
                <button type="button" className="catalog-admin-actions__edit" onClick={() => close(null)}>
                  {pendingPrompt?.cancelLabel ?? 'Annuler'}
                </button>
                <button type="submit" className="register-form__submit">
                  {pendingPrompt?.confirmLabel ?? 'Valider'}
                </button>
              </div>
            </form>
          </DialogPanel>
        </div>
      </Dialog>
    </PromptContext.Provider>
  );
};
