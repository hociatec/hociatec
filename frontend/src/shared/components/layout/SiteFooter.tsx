import { Link } from 'react-router';
import { ExternalLink, Instagram, Linkedin } from 'lucide-react';
import { useEffect, useState } from 'react';

import { fetchNewsArticles, type NewsArticleDto } from '@/features/news/api/newsApi';

const legalLinks = [
  { to: '/legal/cgu', label: 'CGU' },
  { to: '/legal/cgv', label: 'CGV' },
  { to: '/legal/confidentialite', label: 'Confidentialité' },
  { to: '/legal/mentions-legales', label: 'Mentions légales' },
];

const socialLinks = [
  { href: 'https://www.facebook.com/hociatec', label: 'Facebook' },
  { href: '#', label: 'LinkedIn', Icon: Linkedin },
  { href: '#', label: 'TikTok' },
  { href: '#', label: 'X' },
  { href: '#', label: 'Instagram', Icon: Instagram },
];

export const SiteFooter = () => {
  const [latestNews, setLatestNews] = useState<NewsArticleDto[]>([]);

  useEffect(() => {
    let cancelled = false;
    void fetchNewsArticles({ perPage: 3 })
      .then((result) => {
        if (!cancelled) setLatestNews(result.items);
      })
      .catch(() => {
        if (!cancelled) setLatestNews([]);
      });

    return () => {
      cancelled = true;
    };
  }, []);

  return (
  <footer className="site-footer">
    <div className="site-footer__container">
      <div className="site-footer__grid">
        <nav className="site-footer__column" aria-label="Réseaux sociaux">
          <h2>Réseaux sociaux</h2>
          {socialLinks.map(({ href, label, Icon }) => (
            <a
              key={label}
              href={href}
              className="site-footer__link"
              target={href === '#' ? undefined : '_blank'}
              rel={href === '#' ? undefined : 'noreferrer'}
            >
              {Icon ? <Icon aria-hidden="true" /> : <ExternalLink aria-hidden="true" />}
              {label}
            </a>
          ))}
        </nav>

        <div className="site-footer__column">
          <h2>Actualité</h2>
          {latestNews.length > 0 ? (
            latestNews.map((article) => (
              <Link key={article.id} to={`/actualites/${article.slug}`} className="site-footer__link">
                {article.title}
              </Link>
            ))
          ) : (
            <p className="site-footer__tagline">
              Suivez les nouveautés Hociatec, les arrivages matériel et les prochaines annonces.
            </p>
          )}
          <Link to="/actualites" className="site-footer__link">
            Voir les actualités
          </Link>
        </div>

        <div className="site-footer__column site-footer__column--brand">
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
