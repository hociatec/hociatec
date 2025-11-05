import { Link } from 'react-router-dom';

export const SiteFooter = () => (
  <footer className="site-footer">
    <div className="site-footer__container">
      <p className="site-footer__brand">© {new Date().getFullYear()} hociatec</p>
      <nav className="site-footer__links">
        <Link to="/legal/cgu" className="site-footer__link">CGU</Link>
        <Link to="/legal/cgv" className="site-footer__link">CGV</Link>
        <Link to="/legal/confidentialite" className="site-footer__link">Politique de confidentialité</Link>
        <Link to="/legal/mentions-legales" className="site-footer__link">Mentions légales</Link>
        <Link to="/contact" className="site-footer__link">Contact</Link>
      </nav>
    </div>
  </footer>
);
