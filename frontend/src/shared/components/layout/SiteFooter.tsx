import { Link } from 'react-router';
import { Clock3, Facebook, Instagram, Linkedin, Mail, MapPin, Music2, Twitter } from 'lucide-react';
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

const socialLinks = [
  { href: 'https://www.facebook.com/hociatec', label: 'Facebook', Icon: Facebook },
  { href: 'https://www.linkedin.com/company/hociatec', label: 'LinkedIn', Icon: Linkedin },
  { href: 'https://www.tiktok.com/@hociatec', label: 'TikTok', Icon: Music2 },
  { href: 'https://x.com/hociatec', label: 'X', Icon: Twitter },
  { href: 'https://www.instagram.com/hociatec', label: 'Instagram', Icon: Instagram },
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
          <div className="site-footer__column">
            <h2>Réseaux sociaux</h2>
            <nav className="site-footer__social-links" aria-label="Réseaux sociaux">
              {socialLinks.map(({ href, label, Icon }) => (
                <a
                  key={href}
                  href={href}
                  className="site-footer__link site-footer__social-link"
                  target="_blank"
                  rel="noopener noreferrer"
                >
                  <Icon aria-hidden="true" />
                  <span>{label}</span>
                </a>
              ))}
            </nav>
          </div>

          <div className="site-footer__column" aria-label="À propos">
            <h2>À propos</h2>
            <ul className="site-footer__about-info">
              <li>
                <a href={`mailto:${CONTACT_EMAIL}`} className="site-footer__info-row">
                  <Mail aria-hidden="true" />
                  <span>Email : {CONTACT_EMAIL}</span>
                </a>
              </li>
              <li className="site-footer__info-row">
                <MapPin aria-hidden="true" />
                <span>Interventions partout en France</span>
              </li>
              <li className="site-footer__info-row">
                <MapPin aria-hidden="true" />
                <span>Intervention sous 2 heures en Ile-de-France</span>
              </li>
            </ul>
            <nav className="site-footer__about-links" aria-label="Liens À propos">
              <Link to="/contact" className="site-footer__link">
                Contact
              </Link>
              <Link to="/actualites" className="site-footer__link">
                Actualité
              </Link>
              <a href="/#histoire" className="site-footer__link">
                Notre histoire
              </a>
            </nav>
          </div>

          <div className="site-footer__column">
            <h2>Informations légales</h2>
            <nav className="site-footer__links" aria-label="Liens légaux">
              {legalLinks.map((link) => (
                <Link key={link.to} to={link.to} className="site-footer__link">
                  {link.label}
                </Link>
              ))}
            </nav>
          </div>

          <div className="site-footer__column site-footer__hours-column">
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
        </div>

        <div className="site-footer__bottom">
          <p>© {new Date().getFullYear()} Hociatec. Tous droits réservés.</p>
        </div>
      </div>
    </footer>
  );
};
