import { useEffect, useRef, useState } from 'react';
import { useLocation } from 'react-router';

const ALERT_SELECTORS = [
  '.register-form__alert',
  '.alert',
  '.booking__alert',
  '.catalog-admin-alert',
  '.catalog-gallery-alert',
].join(',');

const ERROR_CLASS_HINTS = ['register-form__alert--error', 'alert--error', 'booking__alert--error'];

const SUCCESS_CLASS_HINTS = [
  'register-form__alert--success',
  'alert--success',
  'booking__alert--success',
];

const MAX_ANNOUNCEMENT_LENGTH = 180;

const normalizeAnnouncementText = (value: string | null | undefined) =>
  (value ?? '').replace(/\s+/g, ' ').trim().slice(0, MAX_ANNOUNCEMENT_LENGTH);

const isErrorAlert = (element: Element) =>
  ERROR_CLASS_HINTS.some((className) => element.classList.contains(className)) ||
  /erreur|impossible|échou|echec|invalide|introuvable|required|obligatoire/i.test(
    element.textContent ?? '',
  );

const isSuccessAlert = (element: Element) =>
  SUCCESS_CLASS_HINTS.some((className) => element.classList.contains(className)) ||
  /succès|créé|créée|envoyé|envoyée|enregistré|mise à jour|mis à jour|confirmé/i.test(
    element.textContent ?? '',
  );

const annotateAlert = (element: Element) => {
  if (!(element instanceof HTMLElement)) {
    return;
  }

  const announcement = normalizeAnnouncementText(element.textContent);
  if (announcement === '') {
    return;
  }

  element.setAttribute('aria-label', element.getAttribute('aria-label') ?? announcement);

  if (isErrorAlert(element)) {
    element.setAttribute('role', element.getAttribute('role') ?? 'alert');
    element.setAttribute('aria-live', 'assertive');
  } else if (isSuccessAlert(element)) {
    element.setAttribute('role', element.getAttribute('role') ?? 'status');
    element.setAttribute('aria-live', 'polite');
  } else {
    element.setAttribute('role', element.getAttribute('role') ?? 'status');
    element.setAttribute('aria-live', element.getAttribute('aria-live') ?? 'polite');
  }

  element.setAttribute('aria-atomic', element.getAttribute('aria-atomic') ?? 'true');
};

const annotateAlerts = (root: ParentNode = document) => {
  root.querySelectorAll(ALERT_SELECTORS).forEach(annotateAlert);
};

const ROUTE_ANNOUNCEMENT_KEY = 'hociatec.a11y.route-announcement';

const focusPageHeading = () => {
  const heading = document.querySelector<HTMLElement>('main h1, [role="main"] h1, h1');
  const target = heading ?? document.querySelector<HTMLElement>('main, [role="main"]');

  if (!target) {
    return;
  }

  if (!target.hasAttribute('tabindex')) {
    target.setAttribute('tabindex', '-1');
  }

  target.focus({ preventScroll: true });
};

export const AccessibilityAnnouncer = () => {
  const location = useLocation();
  const previousPathRef = useRef<string | null>(null);
  const previousAnnouncementRef = useRef('');
  const [routeAnnouncement, setRouteAnnouncement] = useState('');

  useEffect(() => {
    annotateAlerts();

    const observer = new MutationObserver((mutations) => {
      mutations.forEach((mutation) => {
        mutation.addedNodes.forEach((node) => {
          if (node instanceof HTMLElement) {
            if (node.matches(ALERT_SELECTORS)) {
              annotateAlert(node);
            }

            annotateAlerts(node);
          }
        });

        if (
          mutation.type === 'characterData' &&
          mutation.target.parentElement?.matches(ALERT_SELECTORS)
        ) {
          annotateAlert(mutation.target.parentElement);
        }
      });
    });

    observer.observe(document.body, {
      childList: true,
      subtree: true,
      characterData: true,
    });

    return () => observer.disconnect();
  }, []);

  useEffect(() => {
    const path = `${location.pathname}${location.search}${location.hash}`;
    if (previousPathRef.current === path) {
      return;
    }

    const isInitialRoute = previousPathRef.current === null;
    previousPathRef.current = path;

    if (isInitialRoute) {
      return;
    }

    const timeoutId = window.setTimeout(() => {
      let announcement = '';

      try {
        announcement = window.sessionStorage.getItem(ROUTE_ANNOUNCEMENT_KEY) ?? '';
        window.sessionStorage.removeItem(ROUTE_ANNOUNCEMENT_KEY);
      } catch {
        /* noop */
      }

      const normalizedAnnouncement = normalizeAnnouncementText(announcement);
      if (normalizedAnnouncement && normalizedAnnouncement !== previousAnnouncementRef.current) {
        previousAnnouncementRef.current = normalizedAnnouncement;
        setRouteAnnouncement(normalizedAnnouncement);
      } else {
        setRouteAnnouncement('');
      }
      focusPageHeading();
    }, 120);

    return () => window.clearTimeout(timeoutId);
  }, [location.hash, location.pathname, location.search]);

  return (
    <div className="sr-only" role="status" aria-live="polite" aria-atomic="true">
      {routeAnnouncement}
    </div>
  );
};
