import { Link } from 'react-router';
import { Clock3, Mail, MapPin } from 'lucide-react';
import { CONTACT_EMAIL } from '@/shared/config/seoConfig';

const legalLinks = [
  { to: '/legal/cgu', label: 'CGU' },
  { to: '/legal/cgv', label: 'CGV' },
  { to: '/legal/confidentialite', label: 'Confidentialité' },
  { to: '/legal/mentions-legales', label: 'Mentions légales' },
];

const openingHours = [
  { key: 'lundi', label: 'Lundi', hours: '09h00 - 20h00' },
  { key: 'mardi', label: 'Mardi', hours: '09h00 - 20h00' },
  { key: 'mercredi', label: 'Mercredi', hours: '09h00 - 20h00' },
  { key: 'jeudi', label: 'Jeudi', hours: '09h00 - 20h00' },
  { key: 'vendredi', label: 'Vendredi', hours: '09h00 - 20h00' },
  { key: 'samedi', label: 'Samedi', hours: '09h00 - 17h00' },
  { key: 'dimanche', label: 'Dimanche', hours: 'Fermé' },
];

export const SiteFooter = () => {
  const currentDayKey = new Intl.DateTimeFormat('fr-FR', {
    weekday: 'long',
    timeZone: 'Europe/Paris',
  })
    .format(new Date())
    .toLowerCase();

  return (
    <footer className="site-footer">
      <div className="site-footer__container">
        <div className="site-footer__grid">
          <div className="site-footer__column" aria-label="À propos">
            <h2>À propos</h2>
            <a href="/#histoire" className="site-footer__link">
              Notre histoire
            </a>
            <Link to="/contact" className="site-footer__link">
              Contact
            </Link>
            <a href={`mailto:${CONTACT_EMAIL}`} className="site-footer__link">
              <Mail aria-hidden="true" />
              {CONTACT_EMAIL}
            </a>
            <span className="site-footer__link site-footer__link--static">
              <MapPin aria-hidden="true" />
              Interventions partout en France
            </span>
            <span className="site-footer__link site-footer__link--static">
              <MapPin aria-hidden="true" />
              Intervention sous 2 heures en Ile-de-France
            </span>
          </div>

          <div className="site-footer__column">
            <h2>
              <Clock3 aria-hidden="true" />
              Horaires d&apos;ouverture
            </h2>
            <div className="site-footer__hours" aria-label="Horaires d'ouverture">
              {openingHours.map((entry) => (
                <div
                  key={entry.key}
                  className={`site-footer__hours-row${entry.key === currentDayKey ? ' is-current' : ''}`}
                >
                  <span>{entry.label}</span>
                  <strong>{entry.hours}</strong>
                </div>
              ))}
            </div>
          </div>

          <div className="site-footer__column">
            <h2>Informations légales</h2>
            <nav className="site-footer__legal-links">
            {legalLinks.map((link) => (
              <Link key={link.to} to={link.to} className="site-footer__link">
                {link.label}
              </Link>
            ))}
            </nav>
          </div>
        </div>

        <div className="site-footer__bottom">
          <p>© {new Date().getFullYear()} Hociatec. Tous droits réservés.</p>
        </div>
      </div>
    </footer>
  );
};
