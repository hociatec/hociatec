export const SiteFooter = () => (
  <footer className="site-footer">
    <div className="site-footer__container">
      <p className="site-footer__brand">© {new Date().getFullYear()} hociatec</p>
      <nav className="site-footer__links">
        <a href="#cgu" className="site-footer__link">
          CGU
        </a>
        <a href="#cgv" className="site-footer__link">
          CGV
        </a>
        <a href="#confidentialite" className="site-footer__link">
          Politique de confidentialité
        </a>
        <a href="/contact" className="site-footer__link">
          Contact
        </a>
      </nav>
    </div>
  </footer>
);
