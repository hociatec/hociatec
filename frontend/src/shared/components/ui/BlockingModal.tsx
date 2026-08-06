import { useEffect, useRef, type KeyboardEvent, type MouseEvent, type ReactNode } from 'react';
import { createPortal } from 'react-dom';
import { useTimeout } from '@/shared/hooks/useTimeout';

type BlockingModalProps = {
  onClose?: () => void;
  children: ReactNode;
  describedBy?: string;
  labelledBy: string;
  panelClassName?: string;
};

export const BlockingModal = ({
  children,
  describedBy,
  labelledBy,
  onClose,
  panelClassName = 'mx-auto w-full max-w-2xl rounded-xl border border-brand-100 bg-white p-6 shadow-2xl',
}: BlockingModalProps) => {
  const overlayRef = useRef<HTMLDivElement>(null);
  const panelRef = useRef<HTMLDivElement>(null);
  const { schedule, clear: clearFocusTimeout } = useTimeout();

  useEffect(() => {
    const previousOverflow = document.body.style.overflow;
    const previouslyFocusedElement =
      document.activeElement instanceof HTMLElement ? document.activeElement : null;
    const modalElement = overlayRef.current;
    const bodyChildren = Array.from(document.body.children).filter(
      (element): element is HTMLElement =>
        element instanceof HTMLElement && element !== modalElement,
    );
    const previousInertStates = bodyChildren.map((element) => ({
      element,
      ariaHidden: element.getAttribute('aria-hidden'),
      inert: element.inert,
    }));

    document.body.style.overflow = 'hidden';
    previousInertStates.forEach(({ element }) => {
      element.inert = true;
      element.setAttribute('aria-hidden', 'true');
    });

    const focusModal = () => {
      const focusableElement = panelRef.current?.querySelector<HTMLElement>(focusableSelector);
      (focusableElement ?? panelRef.current)?.focus({ preventScroll: true });
    };
    const animationFrame = window.requestAnimationFrame(focusModal);
    schedule(focusModal, 0);

    const keepFocusInside = (event: FocusEvent) => {
      if (!modalElement || !event.target || modalElement.contains(event.target as Node)) return;
      focusModal();
    };

    document.addEventListener('focusin', keepFocusInside);

    return () => {
      window.cancelAnimationFrame(animationFrame);
      clearFocusTimeout();
      document.removeEventListener('focusin', keepFocusInside);
      document.body.style.overflow = previousOverflow;
      previousInertStates.forEach(({ element, ariaHidden, inert }) => {
        element.inert = inert;
        if (ariaHidden === null) {
          element.removeAttribute('aria-hidden');
        } else {
          element.setAttribute('aria-hidden', ariaHidden);
        }
      });
      previouslyFocusedElement?.focus({ preventScroll: true });
    };
  }, []);

  const handleBackdropClose = (event: MouseEvent<HTMLDivElement>) => {
    if (!onClose) return;
    if (event.target === event.currentTarget) {
      onClose();
    }
  };

  const handleKeyDown = (event: KeyboardEvent<HTMLDivElement>) => {
    if (event.key === 'Escape') {
      if (!onClose) return;
      event.preventDefault();
      onClose();
      return;
    }

    if (event.key !== 'Tab') return;

    const focusableElements = Array.from(
      panelRef.current?.querySelectorAll<HTMLElement>(focusableSelector) ?? [],
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

  if (typeof document === 'undefined') {
    return null;
  }

  return createPortal(
    <div
      ref={overlayRef}
      className="fixed inset-0 z-[1000] overflow-y-auto bg-brand-900/70 px-4 py-4 sm:py-6"
      role="dialog"
      aria-modal="true"
      aria-labelledby={labelledBy}
      aria-describedby={describedBy}
      onMouseDown={handleBackdropClose}
      onKeyDown={handleKeyDown}
    >
      <div ref={panelRef} className={panelClassName} tabIndex={-1}>
        {children}
      </div>
    </div>,
    document.body,
  );
};

const focusableSelector = [
  '[data-autofocus]',
  '[autofocus]',
  'button:not([disabled])',
  '[href]',
  'input:not([disabled])',
  'select:not([disabled])',
  'textarea:not([disabled])',
  '[tabindex]:not([tabindex="-1"])',
].join(',');
