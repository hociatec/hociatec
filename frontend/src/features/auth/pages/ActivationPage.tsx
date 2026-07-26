import { useEffect, useRef, useState } from 'react';
import { useParams, Link } from 'react-router-dom';
import { SiteLayout } from '@/shared/components/SiteLayout';
import { useDocumentTitle } from '@/shared/hooks/useDocumentTitle';
import { useToast } from '@/shared/components/ui/toast';
import { LoadingState } from '@/shared/components/ui/page-state';
import { verifyAccount } from '../api/authApi';

export const ActivationPage = () => {
  useDocumentTitle('Activation du compte');
  const { token } = useParams<{ token: string }>();
  const [status, setStatus] = useState<'loading' | 'ok' | 'error'>('loading');
  const [message, setMessage] = useState('');
  const toast = useToast();
  const firedRef = useRef(false);

  useEffect(() => {
    if (!token || firedRef.current) return;
    firedRef.current = true;

    verifyAccount(token)
      .then((res) => {
        const msg = res.message ?? 'Votre compte a été activé.';
        setMessage(msg);
        setStatus('ok');
        try {
          toast.show(msg, { variant: 'success' });
        } catch {}
      })
      .catch((err) => {
        const details = (err as Error & { details?: string[] }).details;
        const msg = details?.[0] ?? (err as Error).message;
        setMessage(msg);
        setStatus('error');
        try {
          toast.show(msg, { variant: 'error' });
        } catch {}
      });
  }, [token]);

  return (
    <SiteLayout headerVariant="light">
      <div className="container mx-auto max-w-xl p-4">
        <h1 className="text-2xl font-semibold mb-3">Activation du compte</h1>
        {status === 'loading' && <LoadingState>Vérification en cours...</LoadingState>}
        {status !== 'loading' && (
          <>
            <p className={status === 'ok' ? 'text-green-700' : 'text-red-700'}>{message}</p>
            <div className="mt-4">
              <Link className="text-brand-700 underline" to="/login">
                Aller à la connexion
              </Link>
            </div>
          </>
        )}
      </div>
    </SiteLayout>
  );
};
