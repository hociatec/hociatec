import { useEffect, useMemo } from 'react';
import { Link, useSearchParams } from 'react-router';

import { SiteLayout } from '@/shared/components/layout/SiteLayout';
import { useDocumentTitle } from '@/shared/hooks/useDocumentTitle';

const APP_SCHEME_PREFIX = 'hociatec:///checkout';

const buildAppUrl = (status: string | null, sessionId: string | null) => {
  if (status === 'success' && sessionId) {
    return `${APP_SCHEME_PREFIX}/success?session_id=${encodeURIComponent(sessionId)}`;
  }

  return `${APP_SCHEME_PREFIX}/cancel`;
};

export const MobileCheckoutReturnPage = () => {
  useDocumentTitle("Retour vers l'application");

  const [searchParams] = useSearchParams();
  const status = searchParams.get('status');
  const sessionId = searchParams.get('session_id');
  const targetUrl = useMemo(() => buildAppUrl(status, sessionId), [sessionId, status]);

  useEffect(() => {
    if (typeof window === 'undefined') {
      return;
    }

    const timeoutId = window.setTimeout(() => {
      window.location.replace(targetUrl);
    }, 150);

    return () => window.clearTimeout(timeoutId);
  }, [targetUrl]);

  const isSuccess = status === 'success' && Boolean(sessionId);

  return (
    <SiteLayout headerVariant="light">
      <div className="container mx-auto px-4 py-10">
        <div className="mx-auto max-w-2xl rounded-xl border border-brand-100 bg-white p-8 shadow-sm">
          <h1 className="text-2xl font-semibold text-brand-900">
            {isSuccess ? 'Retour à l’app Hociatec' : 'Paiement interrompu'}
          </h1>
          <p className="mt-4 text-stone-600">
            {isSuccess
              ? "Le paiement est terminé. Retournez dans l’application pour finaliser la commande."
              : "Le paiement n’a pas été finalisé. Vous pouvez rouvrir l’application pour reprendre votre panier."}
          </p>
          <div className="mt-6 flex flex-wrap gap-3">
            <a
              href={targetUrl}
              className="inline-flex items-center rounded-full bg-brand-700 px-5 py-3 text-sm font-semibold text-white transition hover:bg-brand-800"
            >
              Ouvrir l’application
            </a>
            <Link className="inline-flex items-center rounded-full border border-stone-300 px-5 py-3 text-sm font-medium text-stone-700 transition hover:border-stone-400" to="/panier">
              Rester sur le site
            </Link>
          </div>
        </div>
      </div>
    </SiteLayout>
  );
};

export default MobileCheckoutReturnPage;
