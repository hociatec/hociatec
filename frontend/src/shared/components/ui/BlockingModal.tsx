import { useEffect, useRef, type KeyboardEvent, type ReactNode } from 'react';
import { createPortal } from 'react-dom';

type BlockingModalProps = {
  children: ReactNode;
  describedBy?: string;
  labelledBy: string;
  panelClassName?: string;
};

export const BlockingModal = ({
  children,
  describedBy,
  labelledBy,
  panelClassName = 'mx-auto w-full max-w-2xl rounded-xl border border-brand-100 bg-white p-6 shadow-2xl',
}: BlockingModalProps) => {
  const panelRef = useRef<HTMLDivElement>(null);

  useEffect(() => {
    const previousOverflow = document.body.style.overflow;
    const previouslyFocusedElement =
      document.activeElement instanceof HTMLElement ? document.activeElement : null;
    document.body.style.overflow = 'hidden';

    const focusableElement = panelRef.current?.querySelector<HTMLElement>(
      [
        '[autofocus]',
        'button:not([disabled])',
        '[href]',
        'input:not([disabled])',
        'select:not([disabled])',
        'textarea:not([disabled])',
        '[tabindex]:not([tabindex="-1"])',
      ].join(','),
    );
    (focusableElement ?? panelRef.current)?.focus();

    return () => {
      document.body.style.overflow = previousOverflow;
      previouslyFocusedElement?.focus();
    };
  }, []);

  if (typeof document === 'undefined') {
    return null;
  }

  const handleKeyDown = (event: KeyboardEvent<HTMLDivElement>) => {
    if (event.key !== 'Tab') return;

    const focusableElements = Array.from(
      panelRef.current?.querySelectorAll<HTMLElement>(
        [
          'button:not([disabled])',
          '[href]',
          'input:not([disabled])',
          'select:not([disabled])',
          'textarea:not([disabled])',
          '[tabindex]:not([tabindex="-1"])',
        ].join(','),
      ) ?? [],
    );

    if (focusableElements.length === 0) {
      event.preventDefault();
      panelRef.current?.focus();
      return;
    }

    const firstElement = focusableElements[0];
    const lastElement = focusableElements[focusableElements.length - 1];

    if (event.shiftKey && document.activeElement === firstElement) {
      event.preventDefault();
      lastElement?.focus();
      return;
    }

    if (!event.shiftKey && document.activeElement === lastElement) {
      event.preventDefault();
      firstElement?.focus();
    }
  };

  return createPortal(
    <div
      className="fixed inset-0 z-[1000] overflow-y-auto bg-brand-900/70 px-4 py-4 sm:py-6"
      role="dialog"
      aria-modal="true"
      aria-labelledby={labelledBy}
      aria-describedby={describedBy}
      onKeyDown={handleKeyDown}
    >
      <div ref={panelRef} className={panelClassName} tabIndex={-1}>
        {children}
      </div>
    </div>,
    document.body,
  );
};
