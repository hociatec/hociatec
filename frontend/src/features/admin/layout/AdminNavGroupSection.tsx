import { useRef, type KeyboardEvent } from 'react';
import { ChevronDown } from 'lucide-react';
import { Link } from 'react-router';

import type { AdminNavGroup, AdminNavLink } from './adminNavConfig';

type AdminNavGroupSectionProps = {
  group: AdminNavGroup;
  isCurrent: boolean;
  isOpen: boolean;
  isLinkActive: (link: AdminNavLink) => boolean;
  onToggle: () => void;
  onClose: () => void;
};

export const AdminNavGroupSection = ({
  group,
  isCurrent,
  isLinkActive,
  isOpen,
  onToggle,
  onClose,
}: AdminNavGroupSectionProps) => {
  const Icon = group.icon;
  const triggerRef = useRef<HTMLButtonElement>(null);

  const handleKeyDown = (event: KeyboardEvent<HTMLElement>) => {
    if (event.key !== 'Escape' || !isOpen) {
      return;
    }

    event.preventDefault();
    event.stopPropagation();
    onClose();
    triggerRef.current?.focus();
  };

  return (
    <section
      className={`admin-shell__nav-group${isCurrent ? ' is-current' : ''}`}
      onKeyDown={handleKeyDown}
    >
      <button
        ref={triggerRef}
        type="button"
        className="admin-shell__nav-trigger"
        aria-expanded={isOpen}
        aria-controls={`admin-nav-${group.id}`}
        onClick={onToggle}
      >
        <Icon aria-hidden="true" />
        <strong>{group.label}</strong>
        <ChevronDown aria-hidden="true" className="admin-shell__nav-chevron" />
      </button>
      <div id={`admin-nav-${group.id}`} className="admin-shell__submenu" hidden={!isOpen}>
        {group.links.map((link) => (
          <Link key={link.to} to={link.to} className={isLinkActive(link) ? 'is-active' : undefined}>
            <span>{link.label}</span>
          </Link>
        ))}
      </div>
    </section>
  );
};
