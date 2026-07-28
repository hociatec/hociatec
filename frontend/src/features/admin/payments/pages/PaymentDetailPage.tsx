import { useEffect, useState } from 'react';
import { useNavigate, useParams } from 'react-router';

import {
  fetchAdminPaymentById,
  type AdminPaymentDetailDto,
  type AdminPaymentLiveStripeDto,
} from '@/features/orders/api';
import { PaymentDetailContent } from '@/features/admin/payments/components/PaymentDetailContent';
import { PageContainer } from '@/shared/components/PageContainer';
import { FeedbackMessage, LoadingState } from '@/shared/components/ui/page-state';
import { useDocumentTitle } from '@/shared/hooks/useDocumentTitle';

export const PaymentDetailPage = () => {
  const params = useParams();
  const navigate = useNavigate();
  const paymentId = Number(params.paymentId);
  const [payment, setPayment] = useState<AdminPaymentDetailDto | null>(null);
  const [liveStripe, setLiveStripe] = useState<AdminPaymentLiveStripeDto | null>(null);
  const [loading, setLoading] = useState(true);
  const [error, setError] = useState<string | null>(null);

  useDocumentTitle(payment ? `Admin - Paiement ${payment.id}` : 'Admin - Paiement');

  useEffect(() => {
    if (!paymentId) {
      setError('Paiement invalide.');
      setLoading(false);
      return;
    }

    setLoading(true);
    setError(null);
    void fetchAdminPaymentById(paymentId)
      .then((data) => {
        setPayment(data.payment);
        setLiveStripe(data.liveStripe);
      })
      .catch((reason: unknown) =>
        setError(reason instanceof Error ? reason.message : 'Impossible de charger le paiement.'),
      )
      .finally(() => setLoading(false));
  }, [paymentId]);

  return (
    <PageContainer
      size="admin"
      title={payment ? `Paiement #${payment.id}` : 'Paiement'}
      headerActions={
        <button
          type="button"
          className="underline text-sm"
          onClick={() => navigate('/admin/payments')}
        >
          Retour aux paiements
        </button>
      }
    >
      {loading ? <LoadingState>Chargement...</LoadingState> : null}
      {error ? <FeedbackMessage>{error}</FeedbackMessage> : null}
      {payment ? <PaymentDetailContent payment={payment} liveStripe={liveStripe} /> : null}
    </PageContainer>
  );
};
