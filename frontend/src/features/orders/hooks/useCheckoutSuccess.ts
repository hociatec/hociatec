import { useEffect, useState } from 'react';
import { useNavigate, useSearchParams } from 'react-router';
import { useQuery } from '@tanstack/react-query';
import { useCart } from '@/features/cart/hooks/useCart';
import { fetchCheckoutSessionStatus } from '../api';
import { orderQueryKeys } from '@/shared/lib/queryKeys';

export const useCheckoutSuccess = () => {
  const [searchParams] = useSearchParams();
  const navigate = useNavigate();
  const { resetAfterCheckout } = useCart();
  const sessionId = searchParams.get('session_id');
  const [message, setMessage] = useState('Confirmation du paiement en cours...');
  const [attempts, setAttempts] = useState(0);
  const checkoutQuery = useQuery({
    queryKey: orderQueryKeys.checkoutSession(sessionId),
    queryFn: () => fetchCheckoutSessionStatus(sessionId ?? ''),
    enabled: Boolean(sessionId) && attempts < 20,
    refetchInterval: (query) => {
      const data = query.state.data;
      if (!data || data.status === 'pending') return attempts < 20 ? 2000 : false;
      return false;
    },
  });

  useEffect(() => {
    if (!sessionId) {
      setMessage('Session de paiement manquante.');
    }
  }, [sessionId]);

  useEffect(() => {
    if (checkoutQuery.fetchStatus === 'fetching') {
      setAttempts((current) => current + 1);
    }
  }, [checkoutQuery.fetchStatus]);

  useEffect(() => {
    const result = checkoutQuery.data;
    if (!result) return;
    if (result.status === 'paid' && result.orderId) {
      resetAfterCheckout();
      navigate(`/orders/${result.orderId}`, { replace: true, state: { justConfirmed: true } });
      return;
    }
    if (result.status === 'failed' || result.status === 'expired') {
      setMessage('Le paiement n’a pas pu être confirmé. Vous pouvez réessayer depuis le panier.');
      return;
    }
    if (attempts >= 20) {
      setMessage('Le paiement est en cours de finalisation. Rechargez cette page dans quelques secondes.');
      return;
    }
    setMessage('Paiement reçu. Confirmation de la commande en cours...');
  }, [attempts, checkoutQuery.data, navigate, resetAfterCheckout]);

  useEffect(() => {
    if (checkoutQuery.error) {
      setMessage(
        checkoutQuery.error instanceof Error
          ? checkoutQuery.error.message
          : 'Impossible de vérifier le paiement.',
      );
    }
  }, [checkoutQuery.error]);

  return {
    message,
    status:
      !sessionId || checkoutQuery.error || ['failed', 'expired'].includes(checkoutQuery.data?.status ?? '')
        ? 'error'
        : checkoutQuery.isLoading
          ? 'loading'
          : 'pending',
  };
};
