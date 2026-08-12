import { useEffect, useState } from 'react';
import { zodResolver } from '@hookform/resolvers/zod';
import { useForm } from 'react-hook-form';
import { z } from 'zod';
import { SiteLayout } from '@/shared/components/layout/SiteLayout';
import { PublicPageSection, PublicPageShell } from '@/shared/components/layout/PublicPageShell';
import { PublicSubmitButton } from '@/shared/components/forms/PublicForm';
import { FeedbackMessage } from '@/shared/components/ui/page-state';
import { useDocumentTitle } from '@/shared/hooks/useDocumentTitle';
import { useMetaTags } from '@/shared/hooks/useMetaTags';
import { useToast } from '@/shared/components/ui/toast';
import { SITE_URL, LOCAL_BUSINESS_SCHEMA, CONTACT_EMAIL } from '@/shared/config/seoConfig';
import { sendContactMessage } from '../api/contactApi';
import { TextareaField, TextInputField } from '@/shared/forms/FormField';
import { applyServerFieldErrors } from '@/shared/forms/serverErrors';
import { focusFirstInvalidField } from '@/shared/forms/focusFirstInvalidField';
import { useUnsavedChangesWarning } from '@/shared/forms/useUnsavedChangesWarning';
import { getHttpErrorMessage } from '@/shared/lib/httpClient';
import {
  notifyMutationError,
  notifyMutationSuccess,
} from '@/shared/lib/notificationConventions';

const contactSchema = z.object({
  name: z.string().trim().min(1, 'Le nom est requis.').max(100, 'Le nom est trop long.'),
  email: z.string().trim().email('Adresse email invalide.').max(180, 'Email trop long.'),
  subject: z.string().trim().min(1, 'Le sujet est requis.').max(150, 'Sujet trop long.'),
  message: z.string().trim().min(10, 'Le message doit contenir au moins 10 caractères.').max(5000, 'Message trop long.'),
});

type ContactFormValues = z.infer<typeof contactSchema>;

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
  const [submitSuccess, setSubmitSuccess] = useState(false);
  const [submitSuccessMessage, setSubmitSuccessMessage] = useState<string | null>(null);
  const [submitError, setSubmitError] = useState<string | null>(null);
  const {
    formState: { errors, isDirty, isSubmitting },
    handleSubmit,
    register,
    reset,
    setError,
    setFocus,
  } = useForm<ContactFormValues>({
    defaultValues: {
      email: '',
      message: '',
      name: '',
      subject: '',
    },
    resolver: zodResolver(contactSchema),
  });
  useUnsavedChangesWarning(isDirty && !isSubmitting);

  useEffect(() => {
    focusFirstInvalidField(errors, setFocus);
  }, [errors, setFocus]);

  const onSubmit = handleSubmit(async (values) => {
    setSubmitError(null);
    setSubmitSuccess(false);
    setSubmitSuccessMessage(null);
    try {
      const response = await sendContactMessage(values);
      notifyMutationSuccess(toast, response.message ?? 'Votre message a été envoyé.');
      setSubmitSuccess(true);
      setSubmitSuccessMessage(response.message ?? null);
      reset();
    } catch (err) {
      applyServerFieldErrors(err, setError);
      const message = getHttpErrorMessage(err, 'Impossible d’envoyer votre message.');
      setSubmitError(message);
      notifyMutationError(toast, err, 'Impossible d’envoyer votre message.');
    }
  });

  return (
    <SiteLayout headerVariant="light">
      <PublicPageShell
        size="medium"
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
            <TextInputField
              id="contact-name"
              label="Nom"
              placeholder="Votre nom complet"
              maxLength={100}
              error={errors.name}
              {...register('name')}
            />
            <TextInputField
              id="contact-email"
              label="Email"
              type="email"
              placeholder="Votre adresse email"
              maxLength={180}
              error={errors.email}
              {...register('email')}
            />
            <TextInputField
              id="contact-subject"
              label="Sujet"
              placeholder="Sujet de votre demande"
              maxLength={150}
              error={errors.subject}
              {...register('subject')}
            />
            <TextareaField
              id="contact-message"
              label="Message"
              className="h-40"
              placeholder="Détaillez votre besoin ou votre question"
              maxLength={5000}
              error={errors.message}
              {...register('message')}
            />
            <PublicSubmitButton disabled={isSubmitting}>
              {isSubmitting ? 'Envoi…' : 'Envoyer'}
            </PublicSubmitButton>
          </form>
        </PublicPageSection>
      </PublicPageShell>
    </SiteLayout>
  );
};
