import { useEffect, useId, useRef, useState } from 'react';
import { Link } from 'react-router-dom';

interface UserAccountMenuProps {
  onLogout: () => void;
  profileActive?: boolean;
}

export const UserAccountMenu = ({ onLogout, profileActive = false }: UserAccountMenuProps) => {
  const triggerId = useId();
  const panelId = `${triggerId}-panel`;

  const [isOpen, setIsOpen] = useState(false);

  const containerRef = useRef<HTMLDivElement | null>(null);
  const triggerRef = useRef<HTMLButtonElement | null>(null);
  const firstItemRef = useRef<HTMLAnchorElement | null>(null);

  const closeMenu = (options: { focusTrigger?: boolean } = {}) => {
    setIsOpen(false);

    if (options.focusTrigger !== false) {
      triggerRef.current?.focus();
    }
  };

  useEffect(() => {
    if (!isOpen) {
      return undefined;
    }

    const handlePointerDown = (event: PointerEvent) => {
      const target = event.target as Node;
      if (!containerRef.current?.contains(target)) {
        closeMenu({ focusTrigger: false });
      }
    };

    const handleKeydown = (event: KeyboardEvent) => {
      if (event.key === 'Escape') {
        event.preventDefault();
        closeMenu();
      }
    };

    document.addEventListener('pointerdown', handlePointerDown);
    document.addEventListener('keydown', handleKeydown);

    return () => {
      document.removeEventListener('pointerdown', handlePointerDown);
      document.removeEventListener('keydown', handleKeydown);
    };
  }, [isOpen]);

  useEffect(() => {
    if (!isOpen) {
      return;
    }

    firstItemRef.current?.focus();
  }, [isOpen]);

  const handleToggle = () => {
    setIsOpen((prev) => {
      const next = !prev;

      if (!next) {
        triggerRef.current?.focus();
      }

      return next;
    });
  };

  const handleLogout = () => {
    onLogout();
    closeMenu();
  };

  return (
    <div className="site-header__account" ref={containerRef} style={{ display: 'inline-flex', flexDirection: 'column', alignItems: 'flex-end' }}>
      <button
        type="button"
        id={triggerId}
        ref={triggerRef}
        aria-expanded={isOpen}
        aria-controls={panelId}
        className={`site-header__account-trigger${
          isOpen || profileActive ? ' site-header__account-trigger--active' : ''
        }`}
        onClick={handleToggle}
      >
        <span className="site-header__account-label">Mon espace</span>
        <span
          className={`site-header__account-icon${isOpen ? ' site-header__account-icon--open' : ''}`}
          aria-hidden="true"
        >
          <svg width="14" height="14" viewBox="0 0 24 24" focusable="false">
            <path
              d="M6 9l6 6 6-6"
              fill="none"
              stroke="currentColor"
              strokeLinecap="round"
              strokeLinejoin="round"
              strokeWidth="2"
            />
          </svg>
        </span>
      </button>
      <section
        id={panelId}
        role="region"
        aria-labelledby={triggerId}
        aria-hidden={!isOpen}
        hidden={!isOpen}
        className="site-header__account-panel" style={{ position: 'absolute', top: 'calc(100% + 0.75rem)', right: 0, maxWidth: 'min(280px, calc(100vw - 3rem))' }}
      >
        <div className="site-header__account-panel-content">
          <Link
            to="/favorites"
            className="site-header__account-item"
            ref={firstItemRef}
            onClick={() => closeMenu({ focusTrigger: false })}
          >
            Mes favoris
          </Link>
          <Link
            to="/profile"
            className="site-header__account-item"
            onClick={() => closeMenu({ focusTrigger: false })}
          >
            Mon profil
          </Link>
          <Link
            to="/appointments/me"
            className="site-header__account-item"
            onClick={() => closeMenu({ focusTrigger: false })}
          >
            Mes rendez-vous
          </Link>
          <Link
            to="/quotes/me"
            className="site-header__account-item"
            onClick={() => closeMenu({ focusTrigger: false })}
          >
            Mes devis
          </Link>
          <Link
            to="/audits/me"
            className="site-header__account-item"
            onClick={() => closeMenu({ focusTrigger: false })}
          >
            Mes audits
          </Link>
          <Link
            to="/orders/me"
            className="site-header__account-item"
            onClick={() => closeMenu({ focusTrigger: false })}
          >
            Mes commandes
          </Link>
          <button
            type="button"
            className="site-header__account-item site-header__account-item--danger"
            onClick={handleLogout}
          >
            Se déconnecter
          </button>
        </div>
      </section>
    </div>
  );
};
