import { Link } from 'react-router-dom';

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
      <span className="site-header__account-label">Mon espace</span>
    </Link>
    <button
      type="button"
      className="site-header__logout-button"
      onClick={onLogout}
    >
      Se déconnecter
    </button>
  </div>
);
