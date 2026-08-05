import { useId, useState } from 'react';
import type { FormEvent } from 'react';
import { SiteLayout } from '@/shared/components/layout/SiteLayout';
import { PublicPageSection, PublicPageShell } from '@/shared/components/layout/PublicPageShell';
import {
  PublicFormField,
  PublicSubmitButton,
  PublicTextInput,
  PublicTextarea,
} from '@/shared/components/forms/PublicForm';
import { FeedbackMessage } from '@/shared/components/ui/page-state';
import { useDocumentTitle } from '@/shared/hooks/useDocumentTitle';
import { useMetaTags } from '@/shared/hooks/useMetaTags';
import { useToast } from '@/shared/components/ui/toast';
import { SITE_URL, LOCAL_BUSINESS_SCHEMA, CONTACT_EMAIL } from '@/shared/config/seoConfig';
import { sendContactMessage } from '../api/contactApi';
import { logger } from '@/shared/lib/logger';

export const ContactPage = () => {
  useDocumentTitle('Contact');
  useMetaTags({
    title: 'Contact',
    description: 'Contactez-nous pour vos projets, devis, rendez-vous et audits.',
    type: 'website',
    canonicalUrl: `${SITE_URL}/contact`,
    structuredData: {
      '@context': 'https://schema.org',
      '@type': 'ContactPage',
      name: 'Contact — Hociatec',
      url: `${SITE_URL}/contact`,
      description: 'Contactez Hociatec pour vos projets, devis, rendez-vous et audits numériques.',
      publisher: LOCAL_BUSINESS_SCHEMA,
      contactPoint: {
        '@type': 'ContactPoint',
        email: CONTACT_EMAIL,
        contactType: 'customer service',
        areaServed: 'FR',
      },
    },
  });
  const toast = useToast();
  const nameId = useId();
  const emailId = useId();
  const subjectId = useId();
  const messageId = useId();
  const [name, setName] = useState('');
  const [email, setEmail] = useState('');
  const [subject, setSubject] = useState('');
  const [message, setMessage] = useState('');
  const [loading, setLoading] = useState(false);
  const [submitSuccess, setSubmitSuccess] = useState(false);
  const [submitSuccessMessage, setSubmitSuccessMessage] = useState<string | null>(null);
  const [submitError, setSubmitError] = useState<string | null>(null);

  const onSubmit = async (e: FormEvent) => {
    e.preventDefault();
    setLoading(true);
    setSubmitError(null);
    setSubmitSuccess(false);
    setSubmitSuccessMessage(null);
    try {
      const response = await sendContactMessage({ name, email, subject, message });
      try {
        toast.show(response.message ?? 'Votre message a été envoyé.', {
          variant: 'success',
        });
      } catch (error) {
        logger.warn('Unable to display contact success toast.', { error });
      }
      setSubmitSuccess(true);
      setSubmitSuccessMessage(response.message ?? null);
      setName('');
      setEmail('');
      setSubject('');
      setMessage('');
    } catch (err) {
      const details = (err as Error & { details?: string[] }).details;
      setSubmitError(details?.[0] ?? (err as Error).message);
      try {
        toast.show(details?.[0] ?? (err as Error).message, { variant: 'error' });
      } catch (error) {
        logger.warn('Unable to display contact error toast.', { error });
      }
    } finally {
      setLoading(false);
    }
  };

  return (
    <SiteLayout headerVariant="light">
      <PublicPageShell
        size="medium"
        eyebrow="Contact"
        title="Parlons de votre besoin"
        description="Une question sur un devis, un audit ou une intervention ? Envoyez-nous votre demande avec les éléments utiles."
      >
        <PublicPageSection>
          <p className="text-sm text-gray-700">
            Vous pouvez aussi nous écrire directement à{' '}
            <a className="text-brand-700 underline" href={`mailto:${CONTACT_EMAIL}`}>
              {CONTACT_EMAIL}
            </a>
            .
          </p>
        </PublicPageSection>
        {submitSuccess ? (
          <FeedbackMessage variant="success">
            {submitSuccessMessage ?? 'Votre message a été envoyé.'}
          </FeedbackMessage>
        ) : null}
        {submitError ? <FeedbackMessage>{submitError}</FeedbackMessage> : null}
        <PublicPageSection>
          <form onSubmit={onSubmit} className="space-y-5">
            <PublicFormField label="Nom">
              <PublicTextInput
                id={nameId}
                value={name}
                onChange={(e) => setName(e.target.value)}
                placeholder="Votre nom complet"
                required
                maxLength={100}
              />
            </PublicFormField>
            <PublicFormField label="Email">
              <PublicTextInput
                id={emailId}
                type="email"
                value={email}
                onChange={(e) => setEmail(e.target.value)}
                placeholder="Votre adresse email"
                required
                maxLength={180}
              />
            </PublicFormField>
            <PublicFormField label="Sujet">
              <PublicTextInput
                id={subjectId}
                value={subject}
                onChange={(e) => setSubject(e.target.value)}
                placeholder="Sujet de votre demande"
                required
                maxLength={150}
              />
            </PublicFormField>
            <PublicFormField label="Message">
              <PublicTextarea
                id={messageId}
                className="h-40"
                value={message}
                onChange={(e) => setMessage(e.target.value)}
                placeholder="Détaillez votre besoin ou votre question"
                required
                maxLength={5000}
              />
            </PublicFormField>
            <PublicSubmitButton disabled={loading}>
              {loading ? 'Envoi…' : 'Envoyer'}
            </PublicSubmitButton>
          </form>
        </PublicPageSection>
      </PublicPageShell>
    </SiteLayout>
  );
};
