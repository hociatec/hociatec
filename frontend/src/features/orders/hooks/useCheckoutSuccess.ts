import { useEffect, useState } from 'react';
import { useNavigate, useSearchParams } from 'react-router-dom';
import { useCart } from '@/features/cart/hooks/useCart';
import { fetchCheckoutSessionStatus } from '../api';

export const useCheckoutSuccess = () => {
  const [searchParams] = useSearchParams();
  const navigate = useNavigate();
  const { resetAfterCheckout } = useCart();
  const [message, setMessage] = useState('Confirmation du paiement en cours...');
  const [status, setStatus] = useState<'loading' | 'pending' | 'error'>('loading');
  useEffect(() => {
    const sessionId = searchParams.get('session_id');
    if (!sessionId) { setStatus('error'); setMessage('Session de paiement manquante.'); return; }
    let cancelled = false; let attempts = 0;
    const poll = async () => {
      try {
        const result = await fetchCheckoutSessionStatus(sessionId);
        if (cancelled) return;
        if (result.status === 'paid' && result.orderId) { resetAfterCheckout(); navigate(`/orders/${result.orderId}`, { replace: true, state: { justConfirmed: true } }); return; }
        if (result.status === 'failed' || result.status === 'expired') { setStatus('error'); setMessage('Le paiement n’a pas pu être confirmé. Vous pouvez réessayer depuis le panier.'); return; }
        attempts += 1; setStatus('pending'); setMessage('Paiement reçu. Confirmation de la commande en cours...');
        if (attempts < 20) window.setTimeout(() => void poll(), 2000);
        else setMessage('Le paiement est en cours de finalisation. Rechargez cette page dans quelques secondes.');
      } catch (error) { if (!cancelled) { setStatus('error'); setMessage(error instanceof Error ? error.message : 'Impossible de vérifier le paiement.'); } }
    };
    void poll(); return () => { cancelled = true; };
  }, [navigate, resetAfterCheckout, searchParams]);
  return { message, status };
};
