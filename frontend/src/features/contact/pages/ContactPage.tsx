import { useState } from 'react';
import type { FormEvent } from 'react';
import { SiteLayout } from '@/shared/components/SiteLayout';
import { PublicPageSection, PublicPageShell } from '@/shared/components/PublicPageShell';
import { useDocumentTitle } from '@/shared/hooks/useDocumentTitle';
import { useMetaTags } from '@/shared/hooks/useMetaTags';
import { useToast } from '@/shared/components/ui/toast';
import { SITE_URL, LOCAL_BUSINESS_SCHEMA, CONTACT_EMAIL } from '@/shared/config/seoConfig';
import { sendContactMessage } from '../api/contactApi';

export const ContactPage = () => {
  useDocumentTitle('Contact');
  useMetaTags({
    title: 'Contact — hociatec',
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
      } catch {}
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
      } catch {}
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
          <div className="rounded-2xl border border-green-200 bg-green-50 px-4 py-3 text-green-800">
            {submitSuccessMessage ?? 'Votre message a été envoyé.'}
          </div>
        ) : null}
        {submitError ? (
          <div className="rounded-2xl border border-red-200 bg-red-50 px-4 py-3 text-red-800">
            {submitError}
          </div>
        ) : null}
        <PublicPageSection>
        <form onSubmit={onSubmit} className="space-y-5">
          <div>
            <label className="mb-1 block text-sm font-semibold text-brand-900">Nom</label>
            <input
              className="w-full rounded-xl border border-brand-100 bg-white px-4 py-3 text-brand-900 outline-none transition focus:border-brand-400 focus:ring-4 focus:ring-brand-100"
              value={name}
              onChange={(e) => setName(e.target.value)}
              placeholder="Votre nom complet"
              required
              maxLength={100}
            />
          </div>
          <div>
            <label className="mb-1 block text-sm font-semibold text-brand-900">Email</label>
            <input
              type="email"
              className="w-full rounded-xl border border-brand-100 bg-white px-4 py-3 text-brand-900 outline-none transition focus:border-brand-400 focus:ring-4 focus:ring-brand-100"
              value={email}
              onChange={(e) => setEmail(e.target.value)}
              placeholder="Votre adresse email"
              required
              maxLength={180}
            />
          </div>
          <div>
            <label className="mb-1 block text-sm font-semibold text-brand-900">Sujet</label>
            <input
              className="w-full rounded-xl border border-brand-100 bg-white px-4 py-3 text-brand-900 outline-none transition focus:border-brand-400 focus:ring-4 focus:ring-brand-100"
              value={subject}
              onChange={(e) => setSubject(e.target.value)}
              placeholder="Sujet de votre demande"
              required
              maxLength={150}
            />
          </div>
          <div>
            <label className="mb-1 block text-sm font-semibold text-brand-900">Message</label>
            <textarea
              className="h-40 w-full rounded-xl border border-brand-100 bg-white px-4 py-3 text-brand-900 outline-none transition focus:border-brand-400 focus:ring-4 focus:ring-brand-100"
              value={message}
              onChange={(e) => setMessage(e.target.value)}
              placeholder="Détaillez votre besoin ou votre question"
              required
              maxLength={5000}
            />
          </div>
          <button
            disabled={loading}
            className="inline-flex items-center justify-center rounded-full bg-brand-900 px-6 py-3 text-sm font-semibold text-white transition hover:bg-brand-800 disabled:cursor-not-allowed disabled:opacity-60"
          >
            {loading ? 'Envoi…' : 'Envoyer'}
          </button>
        </form>
        </PublicPageSection>
      </PublicPageShell>
    </SiteLayout>
  );
};
