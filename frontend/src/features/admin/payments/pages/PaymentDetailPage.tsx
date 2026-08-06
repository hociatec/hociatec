import { useQuery } from '@tanstack/react-query';
import { useNavigate, useParams } from 'react-router';

import { fetchAdminPaymentById } from '@/features/orders/publicApi';
import { PaymentDetailContent } from '@/features/admin/payments/components/PaymentDetailContent';
import { PageContainer } from '@/shared/components/layout/PageContainer';
import { FeedbackMessage, LoadingState } from '@/shared/components/ui/page-state';
import { useDocumentTitle } from '@/shared/hooks/useDocumentTitle';
import { adminPaymentQueryKeys } from '@/features/admin/payments/queryKeys';
import { parseNullablePositiveInteger } from '@/shared/lib/parsers';

export const PaymentDetailPage = () => {
  const params = useParams();
  const navigate = useNavigate();
  const paymentId = parseNullablePositiveInteger(params.paymentId);
  const paymentQuery = useQuery({
    queryKey: adminPaymentQueryKeys.detail(paymentId),
    queryFn: () => fetchAdminPaymentById(paymentId || 0),
    enabled: paymentId !== null,
  });
  const payment = paymentQuery.data?.payment ?? null;
  const liveStripe = paymentQuery.data?.liveStripe ?? null;
  const error =
    paymentId === null
      ? 'Paiement invalide.'
      : paymentQuery.error instanceof Error
        ? paymentQuery.error.message || 'Impossible de charger le paiement.'
        : null;

  useDocumentTitle(payment ? `Admin - Paiement ${payment.id}` : 'Admin - Paiement');

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
      {paymentQuery.isLoading ? <LoadingState>Chargement...</LoadingState> : null}
      {error ? <FeedbackMessage>{error}</FeedbackMessage> : null}
      {payment ? <PaymentDetailContent payment={payment} liveStripe={liveStripe} /> : null}
    </PageContainer>
  );
};
