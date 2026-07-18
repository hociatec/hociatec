import { useEffect, useState } from 'react';
import { Link, useNavigate, useSearchParams } from 'react-router-dom';

import { useCart } from '@/features/cart/hooks/useCart';
import { fetchCheckoutSessionStatus } from '@/features/orders/api';
import { SiteLayout } from '@/shared/components/SiteLayout';
import { useDocumentTitle } from '@/shared/hooks/useDocumentTitle';

export const CheckoutSuccessPage = () => {
  useDocumentTitle('Confirmation du paiement');

  const [searchParams] = useSearchParams();
  const navigate = useNavigate();
  const { resetAfterCheckout } = useCart();
  const [message, setMessage] = useState('Confirmation du paiement en cours...');
  const [status, setStatus] = useState<'loading' | 'pending' | 'error'>('loading');

  useEffect(() => {
    const sessionId = searchParams.get('session_id');
    if (!sessionId) {
      setStatus('error');
      setMessage('Session de paiement manquante.');
      return;
    }

    let cancelled = false;
    let attempts = 0;

    const poll = async () => {
      try {
        const result = await fetchCheckoutSessionStatus(sessionId);
        if (cancelled) {
          return;
        }

        if (result.status === 'paid' && result.orderId) {
          resetAfterCheckout();
          navigate(`/orders/${result.orderId}`, {
            replace: true,
            state: { justConfirmed: true },
          });
          return;
        }

        if (result.status === 'failed' || result.status === 'expired') {
          setStatus('error');
          setMessage('Le paiement n’a pas pu être confirmé. Vous pouvez réessayer depuis le panier.');
          return;
        }

        attempts += 1;
        setStatus('pending');
        setMessage('Paiement reçu. Confirmation de la commande en cours...');
        if (attempts < 20) {
          window.setTimeout(() => {
            void poll();
          }, 2000);
          return;
        }

        setMessage('Le paiement est en cours de finalisation. Rechargez cette page dans quelques secondes.');
      } catch (error) {
        setStatus('error');
        setMessage(error instanceof Error ? error.message : 'Impossible de vérifier le paiement.');
      }
    };

    void poll();

    return () => {
      cancelled = true;
    };
  }, [navigate, resetAfterCheckout, searchParams]);

  return (
    <SiteLayout>
      <div className="container mx-auto px-4 py-10">
        <div className="mx-auto max-w-2xl rounded-3xl border border-slate-200 bg-white p-8 shadow-sm">
          <h1 className="text-2xl font-semibold text-slate-900">Confirmation du paiement</h1>
          <p className="mt-4 text-slate-600">{message}</p>
          {status === 'pending' ? (
            <p className="mt-2 text-sm text-slate-500">Ne fermez pas cette page pendant la confirmation.</p>
          ) : null}
          {status === 'error' ? (
            <div className="mt-6">
              <Link className="underline text-sm" to="/panier">
                Retour au panier
              </Link>
            </div>
          ) : null}
        </div>
      </div>
    </SiteLayout>
  );
};
