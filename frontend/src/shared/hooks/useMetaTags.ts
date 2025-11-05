export interface MetaTagsOptions {
  title?: string;
  description?: string;
  imageUrl?: string;
  canonicalUrl?: string;
  type?: string; // e.g. website, article
}

/**
 * Lightweight meta tags manager (title handled by useDocumentTitle).
 * Injects/updates description, OpenGraph, and canonical link based on options.
 */
export const useMetaTags = (opts: MetaTagsOptions = {}) => {
  const origin = typeof window !== 'undefined' ? window.location.origin : '';
  const url = opts.canonicalUrl ?? (typeof window !== 'undefined' ? window.location.href : undefined);

  const upsert = (selector: string, create: () => HTMLElement) => {
    let el = document.head.querySelector(selector) as HTMLElement | null;
    if (!el) {
      el = create();
      document.head.appendChild(el);
    }
    return el as HTMLElement;
  };

  if (typeof document === 'undefined') return;

  // Canonical
  if (url) {
    const link = upsert('link[rel="canonical"]', () => {
      const l = document.createElement('link');
      l.setAttribute('rel', 'canonical');
      return l;
    }) as HTMLLinkElement;
    link.href = url;
  }

  // Description
  if (opts.description) {
    const meta = upsert('meta[name="description"]', () => {
      const m = document.createElement('meta');
      m.setAttribute('name', 'description');
      return m;
    });
    meta.setAttribute('content', opts.description);
  }

  // OpenGraph
  const og = (property: string, content?: string) => {
    if (!content) return;
    const meta = upsert(`meta[property="${property}"]`, () => {
      const m = document.createElement('meta');
      m.setAttribute('property', property);
      return m;
    });
    meta.setAttribute('content', content);
  };

  og('og:title', opts.title);
  og('og:description', opts.description);
  og('og:type', opts.type ?? 'website');
  og('og:url', url);
  og('og:image', opts.imageUrl ?? (origin ? origin + '/vite.svg' : undefined));
};

