import { Link } from 'react-router-dom';
import { Mail } from 'lucide-react';

export const SiteFooter = () => (
  <footer className="site-footer">
    <div className="site-footer__container">
      <div className="site-footer__main">
        <div>
          <p className="site-footer__brand">Hociatec</p>
          <p className="site-footer__tagline">
            Vente, location, audit et accompagnement numérique pour particuliers et professionnels.
          </p>
        </div>
        <ul className="site-footer__trust" aria-label="Engagements Hociatec">
          <li>Matériel sélectionné</li>
          <li>Conseil humain</li>
          <li>Services sur mesure</li>
        </ul>
      </div>
      <div className="site-footer__bottom">
        <p>© {new Date().getFullYear()} Hociatec</p>
        <nav className="site-footer__links" aria-label="Liens légaux">
          <Link to="/legal/cgu" className="site-footer__link">
            CGU
          </Link>
          <Link to="/legal/cgv" className="site-footer__link">
            CGV
          </Link>
          <Link to="/legal/confidentialite" className="site-footer__link">
            Confidentialité
          </Link>
          <Link to="/legal/mentions-legales" className="site-footer__link">
            Mentions légales
          </Link>
          <Link to="/contact" className="site-footer__link">
            <Mail aria-hidden="true" />
            Contact
          </Link>
        </nav>
      </div>
    </div>
  </footer>
);
