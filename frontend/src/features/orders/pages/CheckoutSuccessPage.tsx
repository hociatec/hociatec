import { Link } from 'react-router';

import { useCheckoutSuccess } from '../hooks/useCheckoutSuccess';
import { SiteLayout } from '@/shared/components/SiteLayout';
import { useDocumentTitle } from '@/shared/hooks/useDocumentTitle';

export const CheckoutSuccessPage = () => {
  useDocumentTitle('Confirmation du paiement');

  const { message, status } = useCheckoutSuccess();

  return (
    <SiteLayout>
      <div className="container mx-auto px-4 py-10">
        <div className="mx-auto max-w-2xl rounded-xl border border-brand-100 bg-white p-8 shadow-sm">
          <h1 className="text-2xl font-semibold text-brand-900">Confirmation du paiement</h1>
          <p className="mt-4 text-stone-600">{message}</p>
          {status === 'pending' ? (
            <p className="mt-2 text-sm text-stone-500">
              Ne fermez pas cette page pendant la confirmation.
            </p>
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
