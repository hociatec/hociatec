import { createContext, useCallback, useContext, useMemo, useState, type ReactNode } from 'react';

import {
  AlertDialog,
  AlertDialogAction,
  AlertDialogCancel,
  AlertDialogContent,
  AlertDialogDescription,
  AlertDialogFooter,
  AlertDialogHeader,
  AlertDialogTitle,
} from '@/shared/components/ui/alert-dialog';

type ConfirmOptions = {
  title?: string;
  description: ReactNode;
  confirmLabel?: string;
  cancelLabel?: string;
};

type PendingConfirm = ConfirmOptions & {
  resolve: (confirmed: boolean) => void;
};

const ConfirmContext = createContext<((options: ConfirmOptions) => Promise<boolean>) | null>(null);

export const useConfirm = () => {
  const confirm = useContext(ConfirmContext);

  if (!confirm) {
    throw new Error('useConfirm must be used within <ConfirmProvider>');
  }

  return confirm;
};

export const ConfirmProvider = ({ children }: { children: ReactNode }) => {
  const [pendingConfirm, setPendingConfirm] = useState<PendingConfirm | null>(null);

  const confirm = useCallback((options: ConfirmOptions) => (
    new Promise<boolean>((resolve) => {
      setPendingConfirm({ ...options, resolve });
    })
  ), []);

  const close = useCallback(
    (confirmed: boolean) => {
      const current = pendingConfirm;
      setPendingConfirm(null);
      current?.resolve(confirmed);
    },
    [pendingConfirm],
  );

  const value = useMemo(() => confirm, [confirm]);

  return (
    <ConfirmContext.Provider value={value}>
      {children}
      <AlertDialog open={Boolean(pendingConfirm)} onOpenChange={(open) => !open && close(false)}>
        <AlertDialogContent>
          <AlertDialogHeader>
            <AlertDialogTitle>{pendingConfirm?.title ?? 'Confirmer l’action'}</AlertDialogTitle>
            <AlertDialogDescription>{pendingConfirm?.description}</AlertDialogDescription>
          </AlertDialogHeader>
          <AlertDialogFooter>
            <AlertDialogCancel onClick={() => close(false)}>
              {pendingConfirm?.cancelLabel ?? 'Annuler'}
            </AlertDialogCancel>
            <AlertDialogAction onClick={() => close(true)}>
              {pendingConfirm?.confirmLabel ?? 'Confirmer'}
            </AlertDialogAction>
          </AlertDialogFooter>
        </AlertDialogContent>
      </AlertDialog>
    </ConfirmContext.Provider>
  );
};
