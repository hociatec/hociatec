import { Link } from 'react-router-dom';
import { LogOut, UserRound } from 'lucide-react';

interface UserAccountMenuProps {
  onLogout: () => void;
  profileActive?: boolean;
}

export const UserAccountMenu = ({ onLogout, profileActive = false }: UserAccountMenuProps) => (
  <div className="site-header__account">
    <Link
      to="/mon-espace"
      className={`site-header__account-trigger${
        profileActive ? ' site-header__account-trigger--active' : ''
      }`}
    >
      <UserRound aria-hidden="true" />
      <span className="site-header__account-label">Mon espace</span>
    </Link>
    <button
      type="button"
      className="site-header__logout-button"
      onClick={onLogout}
    >
      <LogOut aria-hidden="true" />
      Se déconnecter
    </button>
  </div>
);
