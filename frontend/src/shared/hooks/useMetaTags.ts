import { useEffect } from 'react';

import { DEFAULT_SEO, SITE_URL, toAbsoluteSiteUrl } from '../config/seoConfig';
import { formatDocumentTitle } from './useDocumentTitle';

export interface MetaTagsOptions {
  title?: string;
  description?: string;
  imageUrl?: string;
  imageAlt?: string;
  canonicalUrl?: string;
  type?: string;
  robots?: string;
  siteName?: string;
  locale?: string;
  twitterCard?: 'summary' | 'summary_large_image';
  twitterSite?: string;
  structuredData?: Record<string, unknown> | Record<string, unknown>[];
}

/**
 * Injects/updates SEO critical tags (description, OpenGraph, Twitter, canonical, structured data).
 */
export const useMetaTags = (opts: MetaTagsOptions = {}) => {
  const structuredDataFingerprint = opts.structuredData
    ? JSON.stringify(opts.structuredData)
    : undefined;

  useEffect(() => {
    if (typeof document === 'undefined') return;

    const canonicalUrl =
      opts.canonicalUrl
        ? toAbsoluteSiteUrl(opts.canonicalUrl)
        : typeof window !== 'undefined'
          ? `${window.location.origin}${window.location.pathname}`
          : SITE_URL;
    const imageUrl = opts.imageUrl
      ? toAbsoluteSiteUrl(opts.imageUrl)
      : toAbsoluteSiteUrl(DEFAULT_SEO.ogImagePath);

    const resolved = {
      title: opts.title ?? DEFAULT_SEO.title,
      description: opts.description ?? DEFAULT_SEO.description,
      imageUrl,
      imageAlt: opts.imageAlt ?? `${DEFAULT_SEO.siteName} — visuel`,
      type: opts.type ?? 'website',
      robots: opts.robots ?? DEFAULT_SEO.robots,
      siteName: opts.siteName ?? DEFAULT_SEO.siteName,
      locale: opts.locale ?? DEFAULT_SEO.locale,
      twitterCard: opts.twitterCard ?? DEFAULT_SEO.twitterCard,
      twitterSite: opts.twitterSite,
    };
    const formattedTitle = formatDocumentTitle(resolved.title);

    const upsertMeta = (attribute: 'name' | 'property', key: string, value?: string) => {
      if (!value) return;
      let el = document.head.querySelector(`meta[${attribute}="${key}"]`) as HTMLMetaElement | null;
      if (!el) {
        el = document.createElement('meta');
        el.setAttribute(attribute, key);
        document.head.appendChild(el);
      }
      el.setAttribute('content', value);
    };

    const ensureCanonical = () => {
      if (!canonicalUrl) return;
      let link = document.head.querySelector('link[rel="canonical"]') as HTMLLinkElement | null;
      if (!link) {
        link = document.createElement('link');
        link.setAttribute('rel', 'canonical');
        document.head.appendChild(link);
      }
      link.href = canonicalUrl;
    };

    const ensureStructuredData = () => {
      document.querySelectorAll('script[data-seo-structured]').forEach((node) => {
        node.parentElement?.removeChild(node);
      });

      if (!opts.structuredData) return;

      const entries = Array.isArray(opts.structuredData)
        ? opts.structuredData
        : [opts.structuredData];

      entries.forEach((schema, index) => {
        const script = document.createElement('script');
        script.type = 'application/ld+json';
        script.setAttribute('data-seo-structured', `entry-${index}`);
        script.textContent = JSON.stringify(schema);
        document.head.appendChild(script);
      });
    };

    ensureCanonical();

    document.title = formattedTitle;

    upsertMeta('name', 'description', resolved.description);
    upsertMeta('name', 'robots', resolved.robots);
    upsertMeta('property', 'og:title', formattedTitle);
    upsertMeta('property', 'og:description', resolved.description);
    upsertMeta('property', 'og:type', resolved.type);
    upsertMeta('property', 'og:url', canonicalUrl);
    upsertMeta('property', 'og:image', resolved.imageUrl);
    upsertMeta('property', 'og:image:alt', resolved.imageAlt);
    upsertMeta('property', 'og:site_name', resolved.siteName);
    upsertMeta('property', 'og:locale', resolved.locale);

    upsertMeta('name', 'twitter:card', resolved.twitterCard);
    upsertMeta('name', 'twitter:title', formattedTitle);
    upsertMeta('name', 'twitter:description', resolved.description);
    upsertMeta('name', 'twitter:image', resolved.imageUrl);
    upsertMeta('name', 'twitter:image:alt', resolved.imageAlt);
    if (resolved.twitterSite) {
      upsertMeta('name', 'twitter:site', resolved.twitterSite);
    }

    ensureStructuredData();
  }, [
    opts.title,
    opts.description,
    opts.imageUrl,
    opts.imageAlt,
    opts.canonicalUrl,
    opts.type,
    opts.robots,
    opts.siteName,
    opts.locale,
    opts.twitterCard,
    opts.twitterSite,
    structuredDataFingerprint,
  ]);
};
