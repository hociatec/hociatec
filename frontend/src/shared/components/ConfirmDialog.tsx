import React, { useEffect, useId, useRef } from 'react';

type ConfirmDialogProps = {
  open: boolean;
  title?: string;
  description?: React.ReactNode;
  confirmLabel?: string;
  cancelLabel?: string;
  onConfirm: () => void;
  onCancel: () => void;
};

export const ConfirmDialog: React.FC<ConfirmDialogProps> = ({
  open,
  title = 'Confirmer',
  description,
  confirmLabel = 'Oui',
  cancelLabel = 'Non',
  onConfirm,
  onCancel,
}) => {
  const dialogRef = useRef<HTMLDivElement | null>(null);
  const confirmBtnRef = useRef<HTMLButtonElement | null>(null);
  const titleId = useId();
  const descId = useId();

  useEffect(() => {
    if (!open) return;

    const previousActive = (document.activeElement as HTMLElement | null) ?? null;
    const prevOverflow = document.body.style.overflow;
    document.body.style.overflow = 'hidden';

    const focusDialog = () => {
      // Try to focus confirm first, then dialog container
      if (confirmBtnRef.current) {
        confirmBtnRef.current.focus();
      } else if (dialogRef.current) {
        dialogRef.current.focus();
      }
    };
    const raf = window.requestAnimationFrame(focusDialog);

    const handleKeyDown = (e: KeyboardEvent) => {
      if (e.key === 'Escape') {
        e.preventDefault();
        onCancel();
        return;
      }
      if (e.key === 'Tab') {
        const container = dialogRef.current;
        if (!container) return;
        const focusables = Array.from(
          container.querySelectorAll<HTMLElement>(
            'button, [href], input, select, textarea, [tabindex]:not([tabindex="-1"])',
          ),
        ).filter((el) => !el.hasAttribute('disabled'));
        if (focusables.length === 0) return;
        const first = focusables[0];
        const last = focusables[focusables.length - 1];
        const active = document.activeElement as HTMLElement | null;
        if (e.shiftKey) {
          if (active === first || !container.contains(active)) {
            e.preventDefault();
            last.focus();
          }
        } else {
          if (active === last || !container.contains(active)) {
            e.preventDefault();
            first.focus();
          }
        }
      }
    };
    document.addEventListener('keydown', handleKeyDown);

    return () => {
      window.cancelAnimationFrame(raf);
      document.removeEventListener('keydown', handleKeyDown);
      document.body.style.overflow = prevOverflow;
      if (previousActive && typeof previousActive.focus === 'function') {
        previousActive.focus();
      }
    };
  }, [open, onCancel]);

  if (!open) return null;
  return (
    <div role="dialog" aria-modal="true" style={{ position: 'fixed', inset: 0, zIndex: 50 }}>
      <div style={{ position: 'absolute', inset: 0, background: 'rgba(15,23,42,0.5)' }} onClick={onCancel} />
      <div
        ref={dialogRef}
        aria-labelledby={titleId}
        aria-describedby={descId}
        tabIndex={-1}
        style={{
          position: 'relative',
          maxWidth: 420,
          margin: '10vh auto 0',
          background: '#fff',
          borderRadius: 12,
          boxShadow: '0 10px 30px rgba(0,0,0,0.2)',
          overflow: 'hidden',
        }}
      >
        <div style={{ padding: 18, borderBottom: '1px solid #e2e8f0' }}>
          <h3 id={titleId} style={{ margin: 0, fontSize: 18 }}>{title}</h3>
        </div>
        <div id={descId} style={{ padding: 18, color: '#334155', fontSize: 14 }}>{description}</div>
        <div style={{ padding: 14, display: 'flex', justifyContent: 'flex-end', gap: 10, borderTop: '1px solid #e2e8f0' }}>
          <button type="button" className="catalog-admin-actions__delete" onClick={onCancel}>
            {cancelLabel}
          </button>
          <button ref={confirmBtnRef} type="button" className="catalog-admin-actions__edit" onClick={onConfirm}>
            {confirmLabel}
          </button>
        </div>
      </div>
    </div>
  );
};
