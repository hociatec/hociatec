import { useEffect, useState } from 'react';

import { createTradeIn } from '../api';
import { TradeInFormFields } from '../components/TradeInFormFields';
import { TradeInSuccessPanel } from '../components/TradeInSuccessPanel';
import { emptyTradeInForm } from '../lib/tradeInForm';
import type { TradeInDto, TradeInInput } from '../types';
import { useTradeInMetadata } from '../useTradeInMetadata';
import { useAuth } from '@/features/auth/hooks/useAuth';
import { PublicPageShell } from '@/shared/components/layout/PublicPageShell';
import { SiteLayout } from '@/shared/components/layout/SiteLayout';
import { FeedbackMessage } from '@/shared/components/ui/page-state';
import { useDocumentTitle } from '@/shared/hooks/useDocumentTitle';
import { useMetaTags } from '@/shared/hooks/useMetaTags';
import { SITE_URL } from '@/shared/config/seoConfig';
import { getHttpErrorMessage } from '@/shared/lib/httpClient';

export const TradeInPage = () => {
  useDocumentTitle('Faire reprendre un matériel');
  useMetaTags({
    title: 'Reprise matériel',
    description:
      'Décrivez votre matériel pour obtenir une estimation indicative puis une offre définitive après contrôle.',
    canonicalUrl: `${SITE_URL}/reprise`,
  });
  const { user, status } = useAuth();
  const { categories, conditions } = useTradeInMetadata();
  const [form, setForm] = useState<TradeInInput>(emptyTradeInForm);
  const [result, setResult] = useState<TradeInDto | null>(null);
  const [resultMessage, setResultMessage] = useState<string | null>(null);
  const [error, setError] = useState<string | null>(null);
  const [saving, setSaving] = useState(false);

  useEffect(() => {
    if (status === 'authenticated' && user) {
      setForm((current) => ({
        ...current,
        firstName: user.firstName,
        lastName: user.lastName,
        email: user.email,
        phone: user.phoneNumber,
      }));
    }
  }, [status, user]);

  const update = <K extends keyof TradeInInput>(key: K, value: TradeInInput[K]) =>
    setForm((current) => ({ ...current, [key]: value }));

  const resetForm = () => {
    setResult(null);
    setResultMessage(null);
    setForm(emptyTradeInForm);
  };

  const submit = async (event: React.FormEvent) => {
    event.preventDefault();
    setError(null);
    setSaving(true);
    try {
      const response = await createTradeIn(form, status === 'authenticated');
      setResult(response.item);
      setResultMessage(response.message ?? null);
    } catch (submissionError) {
      setError(getHttpErrorMessage(submissionError));
    } finally {
      setSaving(false);
    }
  };

  if (result) {
    return (
      <SiteLayout headerVariant="light">
        <PublicPageShell
          size="medium"
          eyebrow="Reprise"
          title="Demande envoyée"
          description="Votre demande a été enregistrée. L’estimation sera confirmée après vérification."
        >
          <TradeInSuccessPanel result={result} resultMessage={resultMessage} onReset={resetForm} />
        </PublicPageShell>
      </SiteLayout>
    );
  }

  return (
    <SiteLayout headerVariant="light">
      <PublicPageShell
        eyebrow="Reprise matériel"
        title="Faire reprendre un matériel"
        description="Décrivez votre matériel. Vous recevrez une estimation indicative, puis une offre définitive après contrôle."
      >
        <div className="mx-auto w-full max-w-3xl space-y-6">
          {error ? <FeedbackMessage>{error}</FeedbackMessage> : null}
          <form className="space-y-6" onSubmit={submit}>
            <TradeInFormFields
              categories={categories}
              conditions={conditions}
              form={form}
              isAuthenticated={status === 'authenticated'}
              saving={saving}
              onChange={update}
            />
          </form>
        </div>
      </PublicPageShell>
    </SiteLayout>
  );
};
